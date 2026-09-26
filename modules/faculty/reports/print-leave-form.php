<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/sms2-capstone/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sms2-capstone/includes/authentication.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/sms2-capstone/modules/faculty/controllers/faculty-data.php';

requireAuth();

$requestId = (int) ($_GET['id'] ?? 0);
if ($requestId <= 0) {
    die('Invalid leave request identifier.');
}

$leaveData = null;
try {
    $pdo = facultyDb();
    $sql = "
        SELECT
            lr.*, 
            fp.faculty_id AS faculty_identifier,
            CONCAT_WS(' ', fp.first_name, fp.last_name) AS faculty_name,
            lr.updated_at AS approval_timestamp
        FROM leave_requests lr
        LEFT JOIN faculty_profiles fp ON fp.id = lr.faculty_id
        WHERE lr.id = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $requestId]);
    $leaveData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    die('Database error: ' . $e->getMessage());
}

if (!$leaveData) {
    die('Leave request record not found.');
}

$signatureFile = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Application Form - <?= htmlspecialchars($leaveData['request_ref'] ?? 'LR-' . $requestId) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #525659;
            font-family: Arial, sans-serif;
            color: #333;
        }
        .sheet {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20mm auto;
            padding: 20mm;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            position: relative;
        }
        @media print {
            body {
                background: none;
            }
            .sheet {
                margin: 0;
                box-shadow: none;
                width: 100%;
                padding: 15mm;
            }
            .no-print {
                display: none !important;
            }
        }
        .form-header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .signature-box {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Hidden during print) -->
    <div class="no-print position-fixed top-0 start-50 translate-middle-x mt-3 bg-dark p-2 rounded-pill shadow-lg d-flex gap-2 z-3">
        <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
            <i class="fas fa-print me-1"></i> Print / Save PDF
        </button>
        <button onclick="window.close()" class="btn btn-light btn-sm rounded-pill px-3">
            Close Window
        </button>
    </div>

    <div class="sheet">
        <!-- Header -->
        <div class="form-header text-center">
            <h4 class="fw-bold mb-1">FACULTY LEAVE APPLICATION FORM</h4>
            <p class="text-muted small mb-0">Official System Generated Record</p>
        </div>

        <!-- Reference & Date Info -->
        <div class="row mb-4">
            <div class="col-6">
                <strong>Reference ID:</strong> <?= htmlspecialchars($leaveData['request_ref'] ?? ('LR-' . $requestId)) ?>
            </div>
            <div class="col-6 text-end">
                <strong>Date Filed:</strong> <?= htmlspecialchars($leaveData['created_at'] ?? '') ?>
            </div>
        </div>

        <!-- Faculty Details -->
        <div class="card mb-4 border-dark">
            <div class="card-header bg-light fw-bold text-dark">Faculty Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="text-muted d-block small">Full Name:</span>
                        <span class="fw-semibold fs-6"><?= htmlspecialchars($leaveData['faculty_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted d-block small">Faculty ID:</span>
                        <span class="fw-semibold fs-6"><?= htmlspecialchars($leaveData['faculty_identifier'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Request Details -->
        <div class="card mb-4 border-dark">
            <div class="card-header bg-light fw-bold text-dark">Leave Specifications</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <span class="text-muted d-block small">Leave Type:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($leaveData['leave_type'] ?? '') ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted d-block small">Start Date:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($leaveData['start_date'] ?? '') ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted d-block small">End Date:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($leaveData['end_date'] ?? '') ?></span>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <span class="text-muted d-block small">Total Duration:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($leaveData['total_days'] ?? '') ?> Day(s)</span>
                    </div>
                    <div class="col-md-8">
                        <span class="text-muted d-block small">Current Status:</span>
                        <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($leaveData['status'] ?? 'Pending') ?></span>
                    </div>
                </div>
                <div>
                    <span class="text-muted d-block small mb-1">Reason for Leave:</span>
                    <p class="p-3 bg-light rounded border mb-0"><?= nl2br(htmlspecialchars($leaveData['reason'] ?? '')) ?></p>
                </div>
            </div>
        </div>

        <!-- Authorization & Sign-off -->
        <div class="card border-dark mt-5">
            <div class="card-header bg-light fw-bold text-dark">Authorization & Sign-off</div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <p class="mb-1 small text-muted">Approval Status:</p>
                        <h6 class="fw-bold text-success mb-2">
                            <i class="fas fa-check-circle me-1"></i> <?= ucwords($leaveData['status'] ?? 'Pending') ?>
                        </h6>
                        <p class="mb-0 small text-muted">Timestamp: <strong><?= htmlspecialchars($leaveData['approval_timestamp'] ?? 'N/A') ?></strong></p>
                    </div>
                    <div class="col-md-5 text-center">
                        <span class="text-muted d-block small mb-1">Department Head Signature</span>
                        <div class="signature-box bg-white p-2 border-bottom">
                            <span class="text-success fw-semibold small">Digitally Approved</span>
                        </div>
                        <span class="d-block text-muted small mt-1">Authorized Department Signatory</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>