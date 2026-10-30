<?php
/**
 * Teaching Load partial: stylesheet link and page styles
 */
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<style>
.teaching-load-section { margin-top: 1.5rem; }
.teaching-load-section__head { display: flex; justify-content: space-between; align-items: flex-end; gap: .75rem 1rem; flex-wrap: wrap; margin-bottom: .85rem; }
.teaching-load-list { display: grid; gap: .85rem; }
.teaching-load-item { position: relative; overflow: hidden; padding: 1.15rem 1.25rem 1.15rem 1.45rem; border: 1px solid var(--sms-border); border-radius: .65rem; background: var(--sms-surface); box-shadow: var(--sms-shadow-xs); }
.teaching-load-item::before { position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--sms-primary); content: ""; }
.teaching-load-item--completed::before { background: var(--sms-success); }
.teaching-load-item--rejected::before { background: var(--sms-danger); }
.teaching-load-item__grid { display: grid; grid-template-columns: minmax(220px, .9fr) minmax(250px, 1.05fr) minmax(280px, 1.15fr); gap: .7rem; }
.teaching-load-panel { min-width: 0; padding: .9rem; border: 1px solid var(--sms-border); border-radius: .5rem; background: var(--sms-surface-muted); }
.teaching-load-panel__title { margin: 0 0 .75rem; color: var(--sms-text-muted); font-size: .8rem; font-weight: 700; }
.teaching-load-item__identity { display: flex; align-items: center; gap: .85rem; background: var(--sms-surface); }
.teaching-load-item__avatar { display: grid; place-items: center; flex: 0 0 54px; width: 54px; aspect-ratio: 1; border: 1px solid var(--sms-border); border-radius: 50%; background: var(--sms-primary-xlight); color: var(--sms-primary); font-size: 1rem; font-weight: 800; }
.teaching-load-item__identity h3 { color: var(--sms-heading); overflow-wrap: anywhere; }
.teaching-load-item__identity-copy { min-width: 0; }
.teaching-load-item__details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; margin: 0; }
.teaching-load-item__details div { min-width: 0; }
.teaching-load-item__details dt { margin-bottom: .2rem; color: var(--sms-text-muted); font-size: .72rem; font-weight: 700; }
.teaching-load-item__details dd { margin: 0; color: var(--sms-text); font-size: .85rem; overflow-wrap: anywhere; }
.teaching-load-item__subjects { display: grid; gap: .55rem; }
.teaching-load-item__subject-head,
.teaching-load-item__subject { display: grid; grid-template-columns: minmax(0, 1fr) minmax(70px, .55fr) minmax(54px, .4fr); gap: .4rem; align-items: start; }
.teaching-load-item__subject-head { color: var(--sms-text-muted); font-size: .7rem; font-weight: 700; }
.teaching-load-item__subject { padding: .55rem 0; border-top: 1px solid var(--sms-border); color: var(--sms-text); font-size: .8rem; }
.teaching-load-item__subject-code { min-width: 0; }
.teaching-load-item__subject-code strong,
.teaching-load-item__subject-code span { display: block; overflow-wrap: anywhere; }
.teaching-load-item__subject-code strong { color: var(--sms-heading); }
.teaching-load-item__subject-code span { margin-top: .15rem; color: var(--sms-text-muted); font-size: .75rem; }
.teaching-load-item__controls { min-width: 0; margin-top: .8rem; padding-top: .75rem; border-top: 1px solid var(--sms-border); }
.teaching-load-item__controls textarea { resize: vertical; }
.teaching-load-history { margin-top: .75rem; }
.teaching-load-history summary { width: fit-content; cursor: pointer; color: var(--sms-primary); font-size: .875rem; font-weight: 600; }
.teaching-load-history ol { margin: .5rem 0 0; padding-left: 1.25rem; }
.teaching-load-history li + li { margin-top: .25rem; }
.teaching-load-empty { padding: 1.5rem; border: 1px dashed var(--sms-border); border-radius: .65rem; background: var(--sms-surface-muted); color: var(--sms-text-muted); text-align: center; }
.load-intake-card { overflow: hidden; border-color: var(--sms-border) !important; border-radius: .75rem; background: var(--sms-surface) !important; box-shadow: var(--sms-shadow-sm); }
.load-intake-card__header { display: flex; align-items: center; gap: .85rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--sms-border); background: var(--sms-surface-muted); }
.load-intake-card__icon { display: grid; place-items: center; flex: 0 0 42px; width: 42px; aspect-ratio: 1; border: 1px solid var(--sms-border); border-radius: .6rem; background: var(--sms-primary-xlight); color: var(--sms-primary); }
.load-intake-card__header h2 { color: var(--sms-heading); }
.load-intake-card__step { color: var(--sms-text-muted); font-size: .72rem; font-weight: 700; text-transform: uppercase; }
.load-intake-card__body { padding: 1.25rem; }
.load-intake-field { min-width: 0; }
.load-intake-field label { display: block; margin-bottom: .4rem; color: var(--sms-heading); font-size: .875rem; font-weight: 700; }
.load-intake-field .form-select { min-height: 46px; border-color: var(--sms-input-border); background-color: var(--sms-input-bg); color: var(--sms-text); }
.load-intake-field .form-select:focus { border-color: var(--sms-primary); box-shadow: 0 0 0 .2rem var(--sms-input-focus); }
.load-intake-field .form-select option { background: var(--sms-surface-solid); color: var(--sms-text); }
.load-intake-field__hint { margin: .4rem 0 0; color: var(--sms-text-muted); font-size: .75rem; }
.load-intake-submit { min-height: 46px; padding-inline: 1.15rem; white-space: nowrap; }
.load-intake-steps { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.2rem; }
.load-intake-step { display: inline-flex; align-items: center; gap: .5rem; color: var(--sms-text-muted); font-size: .8rem; font-weight: 700; }
.load-intake-step__number { display: grid; place-items: center; width: 28px; height: 28px; border: 1px solid var(--sms-border); border-radius: 50%; color: var(--sms-text-muted); }
.load-intake-step--active { color: var(--sms-primary); }
.load-intake-step--active .load-intake-step__number { border-color: var(--sms-primary); background: var(--sms-primary-xlight); color: var(--sms-primary); }
.load-intake-step__line { flex: 0 1 56px; height: 1px; background: var(--sms-border); }
.load-intake-assignment-heading { display: flex; align-items: center; gap: .65rem; margin: 1.2rem 0 .8rem; }
.load-intake-assignment-heading__number { display: grid; place-items: center; flex: 0 0 28px; height: 28px; border-radius: 50%; background: var(--sms-primary); color: #fff; font-size: .75rem; font-weight: 800; }
.load-assignment-list { display: grid; gap: .75rem; margin-top: 1rem; }
.load-assignment-card { padding: 1rem; border: 1px solid var(--sms-border); border-radius: .65rem; background: var(--sms-surface-muted); }
.load-assignment-card__header { display: flex; align-items: center; gap: .65rem; margin-bottom: .85rem; }
.load-assignment-card__number { display: grid; place-items: center; flex: 0 0 28px; width: 28px; aspect-ratio: 1; border-radius: 50%; background: var(--sms-primary-xlight); color: var(--sms-primary); font-size: .75rem; font-weight: 800; }
.load-assignment-card__header h4 { flex: 1; margin: 0; color: var(--sms-heading); font-size: .9rem; font-weight: 700; }
.load-assignment-field { min-width: 0; }
.load-assignment-field label { display: block; margin-bottom: .35rem; color: var(--sms-text-muted); font-size: .75rem; font-weight: 700; }
.load-assignment-field .form-control,
.load-assignment-field .form-select { min-height: 42px; border-color: var(--sms-input-border); background-color: var(--sms-input-bg); color: var(--sms-text); }
.load-assignment-field .form-control:focus,
.load-assignment-field .form-select:focus { border-color: var(--sms-primary); box-shadow: 0 0 0 .2rem var(--sms-input-focus); }
.load-assignment-field .form-select option { background: var(--sms-surface-solid); color: var(--sms-text); }
.load-assignment-footer { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--sms-border); }
@media (max-width: 767.98px) {
    .teaching-load-item { padding: 1rem 1rem 1rem 1.2rem; }
    .teaching-load-item__grid { grid-template-columns: minmax(0, 1fr); }
    .teaching-load-panel { padding: .8rem; }
    .load-intake-card__body { padding: 1rem; }
    .load-intake-submit { width: 100%; }
    .load-intake-step { font-size: .72rem; }
    .load-intake-step__line { flex-basis: 24px; }
    .load-assignment-card { padding: .8rem; }
    .load-assignment-footer .btn { width: 100%; }
}
@media (min-width: 768px) and (max-width: 1100px) {
    .teaching-load-item__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .teaching-load-panel--details { grid-column: 1 / -1; }
}
.load-intake-top { display: grid; grid-template-columns: minmax(240px, .8fr) minmax(0, 1.6fr); gap: .7rem; margin-bottom: 1.25rem; }
.load-intake-top--single { grid-template-columns: minmax(0, 1fr); }
.load-assignment-card { position: relative; overflow: hidden; padding: 1.1rem 1.25rem 1.1rem 1.45rem; background: var(--sms-surface); box-shadow: var(--sms-shadow-xs); }
.load-assignment-card::before { position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--sms-primary); content: ""; }
.load-summary { display: flex; gap: .6rem; flex-wrap: wrap; }
.load-summary__item { padding: .35rem .75rem; border: 1px solid var(--sms-border); border-radius: 999px; background: var(--sms-surface-muted); color: var(--sms-text-muted); font-size: .8rem; }
.load-summary__item strong { color: var(--sms-heading); }
@media (max-width: 991.98px) {
    .load-intake-top,
    .load-wizard { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 575.98px) {
}
.load-intake-card { transition: opacity .2s ease; }
.load-intake-card.is-loading { opacity: .7; pointer-events: none; }
#assignmentBuilder { scroll-margin-top: 90px; outline: none; }
@keyframes loadFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
.load-fade-in { animation: loadFadeIn .3s ease both; }
@media (prefers-reduced-motion: reduce) {
    .load-intake-card { transition: none; }
    .load-fade-in { animation: none; }
}
.load-wizard { display: grid; grid-template-columns: minmax(240px, .8fr) minmax(0, 1.6fr); gap: .7rem; align-items: start; }
.load-wizard__side { display: grid; gap: .7rem; }
.load-wizard__stage { min-width: 0; }
.load-wizard .load-assignment-card { padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; overflow: visible; }
.load-wizard .load-assignment-card::before { display: none; }
.load-assignment-card[data-state="saved"] { display: none; }
.load-stage-panel { animation: loadFadeIn .28s ease both; }
.load-stage-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; margin-top: 1.1rem; padding-top: 1rem; border-top: 1px solid var(--sms-border); }
.load-stage-actions--split { justify-content: space-between; }
.load-stage-actions__group { display: flex; gap: .5rem; flex-wrap: wrap; margin-left: auto; }
.load-stage-error { margin: .85rem 0 0; color: var(--sms-danger); font-size: .8rem; font-weight: 600; }
.load-assignment-field .is-invalid { border-color: var(--sms-danger); }
.load-change-link { display: inline-block; margin-top: .35rem; color: var(--sms-primary); font-weight: 600; text-decoration: none; }
.load-change-link:hover { text-decoration: underline; }
.load-intake-step[href] { text-decoration: none; }
.load-intake-step[href]:hover { color: var(--sms-primary); }
.load-saved-list { display: grid; gap: .5rem; margin: 0 0 .75rem; padding: 0; list-style: none; }
.load-saved-item { display: flex; align-items: center; gap: .6rem; padding: .55rem .65rem; border: 1px solid var(--sms-border); border-radius: .5rem; background: var(--sms-surface); }
.load-saved-item__info { display: grid; flex: 1; min-width: 0; }
.load-saved-item__info strong { color: var(--sms-heading); font-size: .85rem; overflow-wrap: anywhere; }
.load-saved-item__info span { color: var(--sms-text-muted); font-size: .75rem; overflow-wrap: anywhere; }
@media (max-width: 575.98px) {
    .load-stage-actions--split { flex-direction: column; gap: .5rem; }
    .load-stage-actions__group { width: 100%; margin-left: 0; flex-direction: column; gap: .5rem; }
    .load-stage-actions__group .btn, .load-stage-actions .btn { width: 100%; flex: 1 1 100%; margin-left: 0 !important; }
    .load-saved-item { flex-direction: column; align-items: flex-start; }
    .load-saved-item .btn { margin-top: .5rem; align-self: flex-end; }
}

/* =====================================================================
   NEW: PREMIUM DEAN/REGISTRAR CARDS 
   ===================================================================== */

/* Slim Pending Card */
.dean-clean-card {
    position: relative;
    background: var(--sms-surface);
    border: 1px solid var(--sms-border);
    border-radius: .65rem;
    box-shadow: var(--sms-shadow-xs);
    margin-bottom: .85rem;
    overflow: hidden;
}
.dean-clean-card::before {
    content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 4px; background: var(--sms-primary);
}
.dean-clean-card__grid {
    display: grid;
    grid-template-columns: 250px 1fr 320px;
    gap: 1.5rem;
    align-items: center;
    padding: 1rem 1.25rem 1rem 1.35rem;
}
.dean-clean-card__title {
    font-size: .9rem; font-weight: 700; color: var(--sms-heading); margin: 0 0 .15rem 0;
}
.dean-clean-card__subtitle {
    font-size: .75rem; color: var(--sms-text-muted); line-height: 1.3;
}
.dean-clean-card__details { min-width: 0; }

.dean-clean-card__col--faculty {
    display: flex; align-items: center; gap: .85rem;
}
.dean-clean-card__col--subjects {
    border-left: 1px solid var(--sms-border);
    border-right: 1px solid var(--sms-border);
    padding: 0 1.5rem;
    min-width: 0;
}
.dean-clean-card__label {
    font-size: .7rem; font-weight: 700; text-transform: uppercase; color: var(--sms-text-muted); margin-bottom: .4rem;
}
.dean-clean-card__subject {
    display: flex; align-items: center; gap: .5rem; font-size: .8rem; padding: .15rem 0;
}
.dean-clean-card__col--actions {
    display: flex; flex-direction: column; gap: .5rem;
}
.dean-clean-card__meta {
    display: flex; align-items: center; gap: .5rem; font-size: .75rem;
}
.dean-clean-card__form textarea {
    resize: none;
}

@media (max-width: 1100px) {
    .dean-clean-card__grid { grid-template-columns: 200px 1fr; }
    .dean-clean-card__col--actions { grid-column: 1 / -1; flex-direction: row; align-items: center; justify-content: space-between; border-top: 1px solid var(--sms-border); padding-top: 1rem; margin-top: .5rem; }
    .dean-clean-card__form { display: flex; gap: .5rem; align-items: center; width: 100%; }
    .dean-clean-card__form textarea { flex: 1; }
}
@media (max-width: 767.98px) {
    .dean-clean-card__grid { grid-template-columns: 1fr; gap: 1rem; padding: 1rem; }
    .dean-clean-card__col--subjects { border-left: 0; border-right: 0; padding: 0; border-top: 1px solid var(--sms-border); border-bottom: 1px solid var(--sms-border); padding-top: .75rem; padding-bottom: .75rem; }
    .dean-clean-card__col--actions { flex-direction: column; align-items: stretch; }
    .dean-clean-card__form { flex-direction: column; }
}

/* Ultra Clean History Card */
.dean-clean-history {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
    gap: 1rem; padding: 1rem 1.25rem;
    border: 1px solid var(--sms-border);
    border-radius: .65rem;
    background: var(--sms-surface);
    box-shadow: var(--sms-shadow-xs);
}
.dean-clean-history__identity { display: flex; align-items: center; gap: .85rem; flex: 1; }
.dean-clean-history__stats { display: flex; gap: .5rem; align-items: center; }
.dean-clean-history__actions { display: flex; justify-content: flex-end; }

@media (max-width: 767.98px) {
    .dean-clean-history { flex-direction: column; align-items: flex-start; }
    .dean-clean-history__actions { width: 100%; justify-content: stretch; }
    .dean-clean-history__actions .btn { width: 100%; }
}

/* Modal Styling */
.dean-modal { background: var(--sms-surface); color: var(--sms-text); border: 1px solid var(--sms-border); }
.dean-modal .modal-header { border-bottom: 1px solid var(--sms-border); }
.dean-modal .modal-footer { border-top: 1px solid var(--sms-border); }
.dean-modal-banner {
    display: flex; align-items: center; gap: 1rem;
    padding: 1rem; border: 1px solid var(--sms-border); border-radius: .5rem;
    background: var(--sms-surface-muted);
}
@media (max-width: 575.98px) {
    .dean-modal-banner { flex-direction: column; text-align: center; }
    .dean-modal-banner .ms-auto { margin-left: 0 !important; margin-top: .5rem; }
}
</style>