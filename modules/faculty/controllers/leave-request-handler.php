<?php
/**
 * LEAVE REQUEST FORM HANDLER
 * Handles form submission, saves to database, and generates PDF with signatures
 * 
 * File: /modules/faculty/controllers/leave-request-handler.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();

header('Content-Type: application/json');

try {
    // Get database connection
    $db = facultyDb();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get form data
    $employeeName = $_POST['employee_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $leaveType = $_POST['leave_type'] ?? '';
    $leaveFrom = $_POST['leave_from'] ?? '';
    $leaveTo = $_POST['leave_to'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $employeeSignature = $_POST['employee_signature'] ?? '';
    $approvalStatus = $_POST['approval_status'] ?? '';
    $deptHeadSignature = $_POST['dept_head_signature'] ?? '';

    // Calculate number of days
    $fromDate = new DateTime($leaveFrom);
    $toDate = new DateTime($leaveTo);
    $days = (int)$fromDate->diff($toDate)->format('%a') + 1;

    // Save to database
    $stmt = $db->prepare("
        INSERT INTO faculty_leave_requests 
        (faculty_id, employee_name, department, leave_type, leave_from, leave_to, 
         number_of_days, notes, employee_signature, approval_status, 
         dept_head_signature, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    if (!$stmt) {
        throw new Exception('Database error: ' . $db->error);
    }

    $facultyId = getCurrentUserId();
    $stmt->bind_param(
        'isssssissss',
        $facultyId,
        $employeeName,
        $department,
        $leaveType,
        $leaveFrom,
        $leaveTo,
        $days,
        $notes,
        $employeeSignature,
        $approvalStatus,
        $deptHeadSignature
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to save leave request: ' . $stmt->error);
    }

    $requestId = $stmt->insert_id;
    $stmt->close();

    // Generate PDF if approved
    $pdfUrl = null;
    if ($approvalStatus === 'Approved') {
        $pdfUrl = generateLeavePDF(
            $requestId,
            $employeeName,
            $department,
            $leaveType,
            $leaveFrom,
            $leaveTo,
            $days,
            $notes,
            $employeeSignature,
            $deptHeadSignature
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Leave request submitted successfully',
        'request_id' => $requestId,
        'pdf_url' => $pdfUrl
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ===================================================================
// FUNCTION: Generate PDF with Signatures
// ===================================================================
function generateLeavePDF(
    $requestId,
    $employeeName,
    $department,
    $leaveType,
    $leaveFrom,
    $leaveTo,
    $days,
    $notes,
    $employeeSignature,
    $deptHeadSignature
) {
    // Check if TCPDF is available
    if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
        // Fallback: Generate HTML PDF using FPDF or similar
        return generateHtmlPdf(
            $requestId, $employeeName, $department, $leaveType,
            $leaveFrom, $leaveTo, $days, $notes,
            $employeeSignature, $deptHeadSignature
        );
    }

    require_once ROOT_PATH . '/vendor/autoload.php';

    // Create PDF using TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document properties
    $pdf->SetCreator('SMS System');
    $pdf->SetAuthor('Faculty Management');
    $pdf->SetTitle('Leave Request Approval');
    $pdf->SetSubject('Leave Request Form');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 11);

    // Header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'LEAVE REQUEST FORM', 0, 1, 'C');
    $pdf->Line(20, 30, 190, 30);

    // Employee Info Section
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(240, 240, 240);

    // Employee Name and Department
    $pdf->SetXY(20, 40);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(85, 8, 'EMPLOYEE NAME', 1, 0, 'L', true);
    $pdf->Cell(85, 8, 'DEPARTMENT', 1, 1, 'L', true);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, 48);
    $pdf->MultiCell(85, 6, $employeeName, 1);
    $pdf->SetXY(105, 48);
    $pdf->MultiCell(85, 6, $department, 1);

    // Leave Type Section
    $pdf->SetXY(20, 56);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(170, 8, 'REASON FOR LEAVE', 1, 1, 'L', true);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, 64);
    $pdf->MultiCell(170, 6, $leaveType, 1);

    // Dates Section
    $pdf->SetXY(20, 72);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(85, 8, 'FROM', 1, 0, 'L', true);
    $pdf->Cell(85, 8, 'TO', 1, 1, 'L', true);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, 80);
    $pdf->Cell(85, 6, formatDate($leaveFrom), 1);
    $pdf->Cell(85, 6, formatDate($leaveTo), 1, 1);

    $pdf->SetXY(20, 86);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Number of Days: ' . $days, 0, 1);

    // Notes Section
    if ($notes) {
        $pdf->SetXY(20, 94);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 6, 'NOTES/COMMENTS', 0, 1);

        $pdf->SetXY(20, 100);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(170, 4, $notes, 1);
    }

    $yPos = $notes ? 115 : 100;

    // Employee Signature
    $pdf->SetXY(20, $yPos);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(80, 6, 'EMPLOYEE SIGNATURE', 0, 1);

    if ($employeeSignature && strpos($employeeSignature, 'data:image') === 0) {
        // Add employee signature image
        $tempFile = tempnam(sys_get_temp_dir(), 'sig');
        file_put_contents($tempFile, base64_decode(str_replace('data:image/png;base64,', '', $employeeSignature)));
        $pdf->SetXY(20, $yPos + 6);
        $pdf->Image($tempFile, 20, $yPos + 6, 60, 25);
        unlink($tempFile);
    }

    // Department Head Signature
    $pdf->SetXY(110, $yPos);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(80, 6, 'APPROVED BY (DEPT HEAD)', 0, 1);

    if ($deptHeadSignature && strpos($deptHeadSignature, 'data:image') === 0) {
        // Add department head signature image
        $tempFile = tempnam(sys_get_temp_dir(), 'sig');
        file_put_contents($tempFile, base64_decode(str_replace('data:image/png;base64,', '', $deptHeadSignature)));
        $pdf->SetXY(110, $yPos + 6);
        $pdf->Image($tempFile, 110, $yPos + 6, 60, 25);
        unlink($tempFile);
    }

    // Approval Status
    $pdf->SetXY(20, $yPos + 35);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(0, 128, 0); // Green for approved
    $pdf->Cell(0, 10, 'STATUS: APPROVED', 0, 1);
    $pdf->SetTextColor(0, 0, 0);

    // Approval Date
    $pdf->SetXY(20, $yPos + 42);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Approved on: ' . date('F d, Y'), 0, 1);

    // Save PDF
    $pdfDir = ROOT_PATH . '/uploads/leave_requests';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }

    $filename = 'Leave_Request_' . $requestId . '_' . date('Ymd_His') . '.pdf';
    $filepath = $pdfDir . '/' . $filename;

    $pdf->Output($filepath, 'F');

    // Return URL to the PDF
    return BASE_URL . '/uploads/leave_requests/' . $filename;
}

// ===================================================================
// FUNCTION: Format Date
// ===================================================================
function formatDate($date) {
    $d = new DateTime($date);
    return $d->format('F d, Y');
}

// ===================================================================
// FALLBACK: Generate HTML-based PDF (if TCPDF not available)
// ===================================================================
function generateHtmlPdf(
    $requestId,
    $employeeName,
    $department,
    $leaveType,
    $leaveFrom,
    $leaveTo,
    $days,
    $notes,
    $employeeSignature,
    $deptHeadSignature
) {
    // Create simple HTML version
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px; }
            .section { border: 1px solid #000; margin-bottom: 15px; padding: 10px; }
            .section-header { background: #f0f0f0; font-weight: bold; padding: 5px; }
            .section-body { padding: 10px; }
            .row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .full-width { grid-column: 1 / -1; }
            .signature-box { height: 100px; border: 1px solid #ccc; margin: 10px 0; }
            .approval-status { color: green; font-weight: bold; font-size: 14px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="header">LEAVE REQUEST FORM</div>

        <div class="section">
            <div class="section-header">Employee Information</div>
            <div class="section-body">
                <div class="row">
                    <div><strong>Employee Name:</strong> ' . htmlspecialchars($employeeName) . '</div>
                    <div><strong>Department:</strong> ' . htmlspecialchars($department) . '</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Leave Request Details</div>
            <div class="section-body">
                <div class="row">
                    <div><strong>Leave Type:</strong> ' . htmlspecialchars($leaveType) . '</div>
                    <div><strong>Number of Days:</strong> ' . $days . '</div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div><strong>From:</strong> ' . date('F d, Y', strtotime($leaveFrom)) . '</div>
                    <div><strong>To:</strong> ' . date('F d, Y', strtotime($leaveTo)) . '</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Signatures</div>
            <div class="section-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <strong>Employee Signature</strong>
                        ' . ($employeeSignature ? '<img src="' . $employeeSignature . '" style="max-width: 150px; max-height: 80px;">' : '<div class="signature-box"></div>') . '
                    </div>
                    <div>
                        <strong>Department Head Signature</strong>
                        ' . ($deptHeadSignature ? '<img src="' . $deptHeadSignature . '" style="max-width: 150px; max-height: 80px;">' : '<div class="signature-box"></div>') . '
                    </div>
                </div>
            </div>
        </div>

        <div class="approval-status">STATUS: APPROVED ✓</div>
        <div><strong>Approved on:</strong> ' . date('F d, Y') . '</div>

        ' . ($notes ? '<div class="section"><div class="section-header">Notes</div><div class="section-body">' . htmlspecialchars($notes) . '</div></div>' : '') . '
    </body>
    </html>
    ';

    // Save as HTML or convert to PDF
    $htmlDir = ROOT_PATH . '/uploads/leave_requests';
    if (!is_dir($htmlDir)) {
        mkdir($htmlDir, 0755, true);
    }

    $filename = 'Leave_Request_' . $requestId . '_' . date('Ymd_His') . '.html';
    $filepath = $htmlDir . '/' . $filename;

    file_put_contents($filepath, $html);

    return BASE_URL . '/uploads/leave_requests/' . $filename;
}