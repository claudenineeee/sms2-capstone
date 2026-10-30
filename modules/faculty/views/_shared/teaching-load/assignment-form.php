<?php
/**
 * Teaching Load partial: Program Chair intake card
 */
$hasSelection = (int) ($selectedFacultyId ?? 0) > 0 && (int) ($selectedTermId ?? 0) > 0;
$isBuildingLoad = $hasSelection && !isset($_GET['change']);
$selectedFaculty = null;
$selectedTermLabel = '';
if ($isBuildingLoad) {
    foreach ($facultyOptions as $option) {
        if ((int) $option['faculty_id'] === (int) $selectedFacultyId) {
            $selectedFaculty = $option;
            break;
        }
    }
    foreach ($terms as $termOption) {
        if ((int) $termOption['term_id'] === (int) $selectedTermId) {
            $selectedTermLabel = $termOption['academic_year'] . ' · ' . $termOption['semester'];
            break;
        }
    }
}
$changeSelectionUrl = '?faculty_id=' . (int) ($selectedFacultyId ?? 0) . '&term_id=' . (int) ($selectedTermId ?? 0) . '&change=1';

// Check if the faculty is Part-Time
$isPartTime = false;
if ($selectedFaculty !== null) {
    $status = strtolower(trim((string)($selectedFaculty['employment_status'] ?? 'Regular')));
    if (strpos($status, 'part') !== false) { 
        $isPartTime = true; 
    }
}
?>

<?php if ($pdo instanceof PDO && in_array($roleKey, $chairRoles, true)): ?>
<section class="card load-intake-card mb-4" id="loadIntakeSection" aria-labelledby="loadIntakeTitle" style="background: var(--sms-surface); border: 1px solid var(--sms-border); border-radius: .75rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div class="p-3 p-md-4">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0 fw-bold" id="loadIntakeTitle" style="color: var(--sms-heading); letter-spacing: -0.3px;">Teaching Load Request</h2>
        </div>

        <?php if ($chairDepartment === ''): ?>
            <div class="alert alert-warning mb-3" style="font-size: 0.85rem;">This account has no linked faculty profile department, so it cannot submit loads yet.</div>
        <?php endif; ?>

        <?php if (!$isBuildingLoad): ?>
            <!-- STEP 1 -->
            <div class="p-3 mb-3 rounded" style="background: var(--sms-surface-muted); border: 1px solid var(--sms-border);">
                <form method="get" action="#assignmentBuilder" id="loadSelectionForm">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="facultyChoice" class="form-label" style="color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600;">Faculty member</label>
                            <select class="form-select" name="faculty_id" id="facultyChoice" required <?= $chairDepartment === '' ? 'disabled' : '' ?> style="background: var(--sms-input-bg); color: var(--sms-text); border-color: var(--sms-border); padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                                <option value="">Choose faculty</option>
                                <?php foreach ($facultyOptions as $faculty): ?>
                                    <option value="<?= (int) $faculty['faculty_id'] ?>" <?= $selectedFacultyId === (int) $faculty['faculty_id'] ? 'selected' : '' ?>>
                                        <?= e(trim($faculty['first_name'] . ' ' . $faculty['last_name']) . ' · ' . $faculty['department']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="termChoice" class="form-label" style="color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600;">Academic term</label>
                            <select class="form-select" name="term_id" id="termChoice" required <?= $chairDepartment === '' ? 'disabled' : '' ?> style="background: var(--sms-input-bg); color: var(--sms-text); border-color: var(--sms-border); padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                                <option value="">Choose term</option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?= (int) $term['term_id'] ?>" <?= $selectedTermId === (int) $term['term_id'] ? 'selected' : '' ?>>
                                        <?= e($term['academic_year'] . ' · ' . $term['semester']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <!-- Shrunk Button -->
                        <button class="btn btn-primary fw-bold" type="submit" <?= $chairDepartment === '' ? 'disabled' : '' ?> style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: .35rem;">
                            Continue to workload <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- STEP 2 -->
            <div id="assignmentBuilder" tabindex="-1">
                <form method="post" id="loadAssignmentForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="submit_load">
                    <input type="hidden" name="faculty_id" value="<?= $selectedFacultyId ?>">
                    <input type="hidden" name="term_id" value="<?= $selectedTermId ?>">
                    
                    <!-- Faculty Banner -->
                    <div class="rounded overflow-hidden mb-3" style="background: var(--sms-surface-muted); border: 1px solid var(--sms-border);">
                        <div class="p-2 px-3 border-bottom" style="border-color: var(--sms-border) !important;">
                            <div style="color: var(--sms-text-muted); font-size: 0.7rem;">Faculty Member</div>
                            <div class="fw-bold" style="color: var(--sms-heading); font-size: 1rem;"><?= e(trim($selectedFaculty['first_name'] . ' ' . $selectedFaculty['last_name'])) ?></div>
                        </div>
                        <div class="row g-0">
                            <div class="col-6 p-2 px-3 border-end" style="border-color: var(--sms-border) !important;">
                                <div style="color: var(--sms-text-muted); font-size: 0.7rem;">Employment Type</div>
                                <div class="fw-bold" style="color: var(--sms-heading); font-size: 0.85rem;"><?= e($selectedFaculty['employment_status'] ?? 'Regular') ?></div>
                            </div>
                            <div class="col-6 p-2 px-3">
                                <div style="color: var(--sms-text-muted); font-size: 0.7rem;">Semester</div>
                                <div class="fw-bold" style="color: var(--sms-heading); font-size: 0.85rem;"><?= e($selectedTermLabel) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Units Selection -->
                    <div class="mb-2">
                        <h4 class="fw-bold mb-2" style="color: var(--sms-heading); font-size: 0.85rem;">Requested Teaching Units</h4>
                        
                        <div class="d-flex flex-column gap-1 mb-3">
                            <?php 
                            // Removed 21 units
                            $unitOptions = [6, 9, 12, 15, 18, 24];
                            foreach ($unitOptions as $units): 
                                $badgeText = '';
                                $badgeStyle = '';
                                $isDefault = false;

                                if ($isPartTime) {
                                    // PART-TIME LOGIC
                                    if ($units == 9) { 
                                        $badgeText = 'Standard Part-Time'; 
                                        $badgeStyle = 'background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);'; 
                                        $isDefault = true; 
                                    } elseif ($units == 15) { 
                                        $badgeText = 'Part-Time Limit'; 
                                        // Orange/Warning color
                                        $badgeStyle = 'background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);'; 
                                    } elseif ($units > 15) { 
                                        $badgeText = 'Overload'; 
                                        $badgeStyle = 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; 
                                    }
                                } else {
                                    // REGULAR / FULL-TIME LOGIC
                                    if ($units == 18) { 
                                        $badgeText = 'Standard Full-Time'; 
                                        $badgeStyle = 'background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);'; 
                                        $isDefault = true; 
                                    } elseif ($units > 18) { 
                                        $badgeText = 'Overload'; 
                                        $badgeStyle = 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);'; 
                                    }
                                }
                            ?>
                                <label class="modern-radio-pill d-block cursor-pointer m-0">
                                    <input type="radio" name="requested_units" value="<?= $units ?>" <?= $isDefault ? 'checked' : '' ?> class="d-none">
                                    
                                    <div class="d-flex align-items-center px-3 py-2 rounded" style="background: var(--sms-surface-muted); border: 1px solid var(--sms-border); transition: all 0.15s ease;">
                                        <div class="radio-circle me-2" style="width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--sms-text-muted); display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;">
                                            <div class="radio-dot" style="width: 8px; height: 8px; border-radius: 50%; background: transparent; transition: all 0.15s ease;"></div>
                                        </div>
                                        
                                        <span class="fw-bold" style="color: var(--sms-text); font-size: 0.85rem;"><?= $units ?> units</span>
                                        
                                        <?php if ($badgeText): ?>
                                            <span class="ms-auto badge" style="<?= $badgeStyle ?> font-size: 0.65rem;"><?= $badgeText ?></span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <p class="mb-3" style="color: var(--sms-text-muted); font-size: 0.7rem;">Illustrative options only. Configure these according to your school's workload policy.</p>

                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary fw-bold" type="submit" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-radius: .35rem;">
                                <i class="fas fa-paper-plane me-2"></i> Submit to Dean
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.modern-radio-pill input:checked ~ div {
    background: var(--sms-primary-xlight) !important;
    border-color: var(--sms-primary) !important;
}
.modern-radio-pill input:checked ~ div .radio-circle {
    border-color: var(--sms-primary) !important;
}
.modern-radio-pill input:checked ~ div .radio-dot {
    background: var(--sms-primary) !important;
}
.modern-radio-pill:hover input:not(:checked) ~ div {
    background: var(--sms-surface) !important;
    border-color: var(--sms-primary-light) !important;
}
</style>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loadSelection = async (url, pushState) => {
        const section = document.getElementById('loadIntakeSection');
        if (!section) { window.location.href = url; return; }
        section.classList.add('is-loading');
        try {
            const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } });
            if (!response.ok) throw new Error('Request failed');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.getElementById('loadIntakeSection');
            if (!next) throw new Error('Card not found');
            section.innerHTML = next.innerHTML;
            if (pushState) history.pushState({}, '', url);
            section.classList.remove('is-loading');
        } catch (error) {
            window.location.href = url;
        }
    };

    document.addEventListener('submit', event => {
        const selectionForm = event.target;
        if (!(selectionForm instanceof HTMLFormElement) || selectionForm.id !== 'loadSelectionForm') return;
        event.preventDefault();
        const params = new URLSearchParams(new FormData(selectionForm));
        selectionForm.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
        loadSelection(window.location.pathname + '?' + params.toString(), true);
    });
    
    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-load-nav]');
        if (!link) return;
        event.preventDefault();
        loadSelection(new URL(link.getAttribute('href'), window.location.href).toString(), true);
    });
});
</script>