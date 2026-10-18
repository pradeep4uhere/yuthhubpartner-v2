@extends('layouts.app')

@section('content')
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
/* =========================================================
   EVENT TRANSACTION SHOW PAGE
   Isolated Professional UI
   ========================================================= */

.event-transaction-show-page {
    min-height: 100vh !important;
    width: 100% !important;
    background:
        radial-gradient(circle at top right, rgba(16, 185, 129, .06), transparent 30%),
        #f5f7fa !important;
    padding: 32px 24px 60px !important;
    box-sizing: border-box !important;
    color: black;
}

.event-transaction-show-page *,
.event-transaction-show-page *::before,
.event-transaction-show-page *::after {
    box-sizing: border-box !important;
}

.event-transaction-show-content {
    width: 100% !important;
    max-width: 1380px !important;
    margin: 0 auto !important;
}

/* =========================================================
   TOP HEADER
   ========================================================= */

.event-transaction-show-content > .admin-users-header {
    width: 100% !important;
    max-width: none !important;
    margin: 0 0 28px !important;
    padding: 0 !important;
}

.event-transaction-show-content > .admin-users-header a {
    color: #475569 !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
}

.event-transaction-show-content > .admin-users-header a:hover {
    color: #0f172a !important;
}

.event-transaction-show-content > .admin-users-header button {
    color: #64748b !important;
    font-size: 13px !important;
    font-weight: 600 !important;
}

/* =========================================================
   PAGE HEADING
   ========================================================= */

.event-transaction-show-content > .admin-partners-heading {
    display: flex !important;
    align-items: flex-end !important;
    justify-content: space-between !important;
    gap: 30px !important;
    margin: 0 0 28px !important;
    padding: 0 !important;
}

.event-transaction-show-content > .admin-partners-heading h1 {
    margin: 3px 0 0 !important;
    color: #0f172a !important;
    font-size: 34px !important;
    line-height: 1.15 !important;
    font-weight: 800 !important;
    letter-spacing: -1px !important;
}

.event-transaction-show-content .admin-users-intro {
    margin: 9px 0 0 !important;
    color: #64748b !important;
    font-size: 14px !important;
    line-height: 1.6 !important;
}

.event-transaction-show-content .admin-auth-eyebrow {
    margin: 0 !important;
    color: #10b981 !important;
    font-size: 10px !important;
    line-height: 1.4 !important;
    font-weight: 800 !important;
    letter-spacing: .12em !important;
}

/* =========================================================
   PANELS
   ========================================================= */

.event-transaction-show-content > .admin-partners-panel {
    width: 100% !important;
    margin: 0 0 20px !important;
    padding: 0 !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow:
        0 1px 2px rgba(15, 23, 42, .03),
        0 10px 30px rgba(15, 23, 42, .045) !important;
    overflow: hidden !important;
}

.event-transaction-show-content > .admin-partners-panel:last-child {
    margin-bottom: 0 !important;
}

/* =========================================================
   SECTION HEADING
   ========================================================= */

.event-transaction-show-content
.admin-partners-panel
> .admin-partners-heading {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    min-height: 82px !important;
    margin: 0 !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid #edf1f5 !important;
    background: #ffffff !important;
}

.event-transaction-show-content
.admin-partners-panel
> .admin-partners-heading h2 {
    margin: 3px 0 0 !important;
    color: #172033 !important;
    font-size: 17px !important;
    line-height: 1.3 !important;
    font-weight: 750 !important;
    letter-spacing: -.2px !important;
}

/* =========================================================
   STATUS CARD
   ========================================================= */

.event-transaction-show-content .admin-event-status-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    min-height: 105px !important;
    padding: 24px 26px !important;
    background:
        linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
}

.event-transaction-show-content
.admin-event-status-header h2 {
    margin: 4px 0 0 !important;
    color: #0f172a !important;
    font-size: 19px !important;
    font-weight: 750 !important;
}

/* =========================================================
   STATUS BADGE
   ========================================================= */

.event-transaction-show-content .admin-partner-status {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 34px !important;
    padding: 7px 14px !important;
    border-radius: 999px !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: .02em !important;
    white-space: nowrap !important;
}

.event-transaction-show-content
.admin-partner-status--active {
    background: #dcfce7 !important;
    color: #15803d !important;
}

.event-transaction-show-content
.admin-partner-status--inactive {
    background: #fee2e2 !important;
    color: #b91c1c !important;
}

.event-transaction-show-content
.admin-partner-status--pending {
    background: #fef3c7 !important;
    color: #b45309 !important;
}

/* =========================================================
   INFORMATION GRID
   ========================================================= */

.event-transaction-show-content .admin-event-details-grid {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 0 !important;
    width: 100% !important;
    background: #ffffff !important;
}

.event-transaction-show-content .admin-event-details-grid > div {
    min-width: 0 !important;
    min-height: 105px !important;
    padding: 20px 22px !important;
    background: #ffffff !important;
    border: 0 !important;
    border-right: 1px solid #edf1f5 !important;
    border-bottom: 1px solid #edf1f5 !important;
}

.event-transaction-show-content
.admin-event-details-grid > div:nth-child(4n) {
    border-right: 0 !important;
}

.event-transaction-show-content
.admin-event-details-grid
> div:nth-last-child(-n+4) {
    border-bottom: 0 !important;
}

.event-transaction-show-content
.admin-event-details-grid
small {
    display: block !important;
    margin: 0 0 8px !important;
    color: #94a3b8 !important;
    font-size: 10px !important;
    line-height: 1.3 !important;
    font-weight: 800 !important;
    letter-spacing: .08em !important;
    text-transform: uppercase !important;
}

.event-transaction-show-content
.admin-event-details-grid
strong {
    display: block !important;
    max-width: 100% !important;
    color: #1e293b !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    font-weight: 700 !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
}

.event-transaction-show-content
.admin-event-details-grid
> div:nth-child(4)
strong {
    color: #059669 !important;
    font-size: 18px !important;
    font-weight: 800 !important;
}

/* =========================================================
   DOCUMENTS
   ========================================================= */

.event-transaction-show-content .admin-event-documents {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 16px !important;
    padding: 24px !important;
    background: #ffffff !important;
}

.event-transaction-show-content .admin-event-document {
    min-width: 0 !important;
    padding: 18px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    transition: all .2s ease !important;
}

.event-transaction-show-content .admin-event-document:hover {
    background: #ffffff !important;
    border-color: #cbd5e1 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 5px 15px rgba(15, 23, 42, .05) !important;
}

.event-transaction-show-content
.admin-event-document
small {
    display: block !important;
    margin-bottom: 10px !important;
    color: #030f1e !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    letter-spacing: .07em !important;
    text-transform: uppercase !important;
}

.event-transaction-show-content
.admin-event-document
a {
    color: #e7e9ee !important;
    font-size: 13px !important;
    font-weight: 750 !important;
    text-decoration: none !important;

}

.event-transaction-show-content
.admin-event-document
a:hover {
    text-decoration: underline !important;
}

.event-transaction-show-content
.admin-event-document
span {
    color: #828080 !important;
    font-size: 13px !important;
}

/* =========================================================
   COMMENT
   ========================================================= */

.event-transaction-show-content .admin-event-comment {
    margin: 0 !important;
    padding: 22px 24px !important;
    background: #fffbeb !important;
    border: 0 !important;
    border-left: 4px solid #f59e0b !important;
    border-radius: 0 !important;
    color: #57534e !important;
    font-size: 14px !important;
    line-height: 1.7 !important;
}

/* =========================================================
   ADMIN ACTION AREA
   ========================================================= */

.event-transaction-show-content .admin-event-actions {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 24px !important;
    background: #ffffff !important;
}

.event-transaction-show-content
.admin-event-actions
form {
    margin: 0 !important;
}

.event-transaction-show-content
.admin-event-actions
a,
.event-transaction-show-content
.admin-event-actions
button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 42px !important;
    padding: 10px 17px !important;
    border: 1px solid #d7dee7 !important;
    border-radius: 9px !important;
    background: #ffffff !important;
    color: #334155 !important;
    font-family: inherit !important;
    font-size: 12px !important;
    font-weight: 750 !important;
    line-height: 1 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: all .2s ease !important;
}

.event-transaction-show-content
.admin-event-actions
a:hover,
.event-transaction-show-content
.admin-event-actions
button:hover {
    border-color: #94a3b8 !important;
    background: #f8fafc !important;
    color: #0f172a !important;
}

.event-transaction-show-content
.admin-event-actions
.btn-primary {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
}

.event-transaction-show-content
.admin-event-actions
.btn-success {
    background: #059669 !important;
    border-color: #059669 !important;
    color: #ffffff !important;
}

.event-transaction-show-content
.admin-event-actions
.btn-danger {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}

/* =========================================================
   REJECT FORM
   ========================================================= */

.event-transaction-show-content
.admin-event-actions
details {
    width: 100% !important;
    max-width: 620px !important;
}

.event-transaction-show-content
.admin-event-actions
summary {
    display: inline-flex !important;
    align-items: center !important;
    min-height: 42px !important;
    padding: 10px 17px !important;
    border: 1px solid #fecaca !important;
    border-radius: 9px !important;
    background: #fff !important;
    color: #dc2626 !important;
    font-size: 12px !important;
    font-weight: 750 !important;
    cursor: pointer !important;
}

.event-transaction-show-content
.admin-event-actions
textarea {
    display: block !important;
    width: 100% !important;
    min-height: 120px !important;
    padding: 13px 15px !important;
    border: 1px solid #d7dee7 !important;
    border-radius: 10px !important;
    background: #ffffff !important;
    color: #1e293b !important;
    font-family: inherit !important;
    font-size: 13px !important;
    resize: vertical !important;
    outline: none !important;
}

.event-transaction-show-content
.admin-event-actions
textarea:focus {
    border-color: #94a3b8 !important;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, .05) !important;
}

/* =========================================================
   TIMELINE
   ========================================================= */

.event-transaction-show-content .admin-event-timeline {
    position: relative !important;
    padding: 28px 28px 28px 34px !important;
    background: #ffffff !important;
}

.event-transaction-show-content .admin-event-timeline::before {
    content: "" !important;
    position: absolute !important;
    left: 47px !important;
    top: 35px !important;
    bottom: 35px !important;
    width: 1px !important;
    background: #e2e8f0 !important;
}

.event-transaction-show-content
.admin-event-timeline-item {
    position: relative !important;
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    gap: 25px !important;
    padding: 0 0 28px 48px !important;
}

.event-transaction-show-content
.admin-event-timeline-item:last-child {
    padding-bottom: 0 !important;
}

.event-transaction-show-content
.admin-event-timeline-item::before {
    content: "" !important;
    position: absolute !important;
    left: 8px !important;
    top: 2px !important;
    width: 15px !important;
    height: 15px !important;
    border: 4px solid #ffffff !important;
    border-radius: 50% !important;
    background: #10b981 !important;
    box-shadow: 0 0 0 1px #cbd5e1 !important;
    z-index: 2 !important;
}

.event-transaction-show-content
.admin-event-timeline-item
strong {
    display: block !important;
    margin-bottom: 5px !important;
    color: #1e293b !important;
    font-size: 13px !important;
    font-weight: 800 !important;
}

.event-transaction-show-content
.admin-event-timeline-item
p {
    margin: 5px 0 0 !important;
    color: #64748b !important;
    font-size: 12px !important;
    line-height: 1.6 !important;
}

.event-transaction-show-content
.admin-event-timeline-item
small {
    color: #94a3b8 !important;
    font-size: 10px !important;
}

.admin-event-actions {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 1px;
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 1100px) {

    .event-transaction-show-content
    .admin-event-details-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-child(4n) {
        border-right: 1px solid #edf1f5 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-child(2n) {
        border-right: 0 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-last-child(-n+4) {
        border-bottom: 1px solid #edf1f5 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-last-child(-n+2) {
        border-bottom: 0 !important;
    }
}

@media (max-width: 760px) {

    .event-transaction-show-page {
        padding: 20px 14px 40px !important;
    }

    .event-transaction-show-content
    > .admin-partners-heading {
        align-items: flex-start !important;
        flex-direction: column !important;
    }

    .event-transaction-show-content
    > .admin-partners-heading
    h1 {
        font-size: 28px !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid {
        grid-template-columns: 1fr !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div {
        border-right: 0 !important;
        border-bottom: 1px solid #edf1f5 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:last-child {
        border-bottom: 0 !important;
    }

    .event-transaction-show-content
    .admin-event-documents {
        grid-template-columns: 1fr !important;
        padding: 18px !important;
    }

    .event-transaction-show-content
    .admin-event-status-header {
        align-items: flex-start !important;
        flex-direction: column !important;
    }

    .event-transaction-show-content
    .admin-event-actions {
        align-items: stretch !important;
        flex-direction: column !important;
    }

    .event-transaction-show-content
    .admin-event-actions
    a,
    .event-transaction-show-content
    .admin-event-actions
    button {
        width: 100% !important;
    }

    .event-transaction-show-content
    .admin-event-timeline-item {
        grid-template-columns: 1fr !important;
        gap: 5px !important;
    }
}
</style>

<style>

/* Event Transaction Edit Form */
.et-edit-form {
    width: 100%;
    margin: 20px 0;
    padding: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.et-edit-form__header {
    padding: 22px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.et-edit-form__header h3 {
    margin: 0;
    color: #0f172a;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.4;
}

.et-edit-form__header p {
    margin: 5px 0 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.6;
}

.et-edit-form__body {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px;
}

.et-form-field {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
}

.et-form-field label {
    display: block;
    margin-bottom: 8px;
    color: #334155;
    font-size: 14px;
    font-weight: 600;
}

.et-form-field input,
.et-form-field select,
.et-form-field textarea {
    display: block;
    box-sizing: border-box;
    width: 100%;
    min-height: 44px;
    margin: 0;
    padding: 11px 13px;
    color: #0f172a;
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.et-form-field select {
    cursor: pointer;
}

.et-form-field textarea {
    min-height: 115px;
    resize: vertical;
}

.et-form-field input::placeholder,
.et-form-field textarea::placeholder {
    color: #94a3b8;
}

.et-form-field input:focus,
.et-form-field select:focus,
.et-form-field textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.et-form-field input:hover,
.et-form-field select:hover,
.et-form-field textarea:hover {
    border-color: #94a3b8;
}

.et-form-error {
    display: block;
    margin-top: 6px;
    color: #dc2626;
    font-size: 12px;
    font-weight: 500;
}

.et-form-actions {
    display: flex;
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.et-btn-cancel,
.et-btn-update {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-height: 42px;
    padding: 10px 18px;
    border-radius: 7px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.et-btn-cancel {
    color: #334155;
    background: #ffffff;
    border: 1px solid #cbd5e1;
}

.et-btn-cancel:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.et-btn-update {
    color: #ffffff;
    background: #2563eb;
    border: 1px solid #2563eb;
}

.et-btn-update:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

.et-btn-update:focus-visible,
.et-btn-cancel:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.25);
    outline-offset: 2px;
}

@media (max-width: 600px) {
    .et-edit-form__header {
        padding: 18px;
    }

    .et-edit-form__body {
        padding: 18px;
        gap: 17px;
    }

    .et-edit-form__header h3 {
        font-size: 18px;
    }

    .et-form-actions {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .et-btn-cancel,
    .et-btn-update {
        width: 100%;
    }
}
.et-edit-form__body {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    padding: 24px;
    align-items: start;
}

.et-form-field {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
}

.et-form-field:has(textarea) {
    grid-column: 1 / -1;
}

.et-form-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 18px;
    border-top: 1px solid #e2e8f0;
}

/* Tablet: 2 columns */
@media (max-width: 900px) {
    .et-edit-form__body {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Mobile: 1 column */
@media (max-width: 600px) {
    .et-edit-form__body {
        grid-template-columns: 1fr;
        padding: 18px;
    }

    .et-form-field:has(textarea),
    .et-form-actions {
        grid-column: 1;
    }
}
</style>

<style>
.admin-event-document {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 18px;
    margin-bottom: 12px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.03);
    transition: border-color .2s ease, box-shadow .2s ease;
}

.admin-event-document:hover {
    border-color: #cbd5e1;
    box-shadow: 0 5px 14px rgba(15, 23, 42, 0.06);
}

.admin-document-info {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
}

.admin-document-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 21px;
}

.admin-document-text {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.admin-document-text small {
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.admin-document-filename {
    color: #111827;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.admin-document-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.admin-document-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 13px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
}

.admin-document-view {
    color: black;
}

.admin-document-view:hover {
    color: #6d1818;
}

.admin-document-download {
    color: #03214c;
}

.admin-document-download:hover {
    color: #021640;
    border-color: #93c5fd;
}

.admin-document-empty {
    color: #94a3b8;
    font-size: 13px;
    white-space: nowrap;
}

@media (max-width: 650px) {
    .admin-event-document {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-document-actions {
        width: 100%;
        flex-wrap: wrap;
    }

    .admin-document-btn {
        flex: 1;
    }
}

i.fas.fa-download {
    color: black !important;
}





.event-success-alert {
    display: flex !important;
    align-items: flex-start;
    gap: 13px;
    position: relative;
    width: 100%;
    box-sizing: border-box;
    padding: 16px 45px 16px 17px;
    margin: 0 0 22px;
    background: #ecfdf5 !important;
    border: 1px solid #a7f3d0 !important;
    border-left: 5px solid #10b981 !important;
    border-radius: 10px;
    color: #065f46 !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
}

.event-success-alert__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #10b981;
    color: #fff !important;
    font-size: 17px;
    font-weight: 700;
}

.event-success-alert__content {
    flex: 1;
    min-width: 0;
}

.event-success-alert__content strong {
    display: block;
    margin: 0 0 4px;
    color: #065f46 !important;
    font-size: 14px;
    font-weight: 700;
}

.event-success-alert__content p {
    margin: 0;
    color: #047857 !important;
    font-size: 13px;
    line-height: 1.6;
}

.event-success-alert__close {
    position: absolute;
    top: 10px;
    right: 12px;
    padding: 0 5px;
    border: none;
    background: transparent;
    color: #047857 !important;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
}

.event-success-alert__close:hover {
    color: #064e3b !important;
}

@media (max-width: 576px) {
    .event-success-alert {
        padding: 13px 38px 13px 12px;
        gap: 10px;
    }
}

</style>
<style>

.status-transition {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 9px;
    margin: 10px 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 11px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.4;
    border: 1px solid transparent;
    letter-spacing: 0.2px;
}

.status-arrow {
    color: #94a3b8;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
}

/* Neutral */
.status-secondary {
    color: #db2121;
    background: #f1f5f9;
    border-color: #e2e8f0;
}

/* In progress / pending */
.status-warning {
    color: #92400e;
    background: #fef3c7;
    border-color: #fde68a;
}

/* Successful */
.status-success {
    color: #166534;
    background: #dcfce7;
    border-color: #bbf7d0;
}

/* Rejected */
.status-danger {
    color: #991b1b;
    background: #fee2e2;
    border-color: #fecaca;
}

/* Returned */
.status-info {
    color: #075985;
    background: #e0f2fe;
    border-color: #bae6fd;
}

/* Completed */
.status-primary {
    color: #3730a3;
    background: #e0e7ff;
    border-color: #c7d2fe;
}

</style>



@php
    $workflowStatus = $eventTransaction->workflow_status;

    $statusClass = match ($workflowStatus) {
        'approved' => 'active',
        'rejected' => 'inactive',
        default => 'pending',
    };

    $statusLabel = match ($workflowStatus) {
        'under_review' => 'Under Review',
        'resubmitted' => 'Resubmitted',
        default => ucfirst($workflowStatus ?: 'Unknown'),
    };
@endphp

<main class="event-transaction-show-page">

    {{-- Header --}}
    <header class="admin-users-header">

        <a href="{{ route('admin.event-transactions.index') }}">
            &larr; Event Transactions
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit">
                Sign out
            </button>
        </form>

    </header>




    <section class="event-transaction-show-content">

        {{-- Page Heading --}}
        <div class="admin-partners-heading">

            <div>

                <p class="admin-auth-eyebrow">
                    EVENT TRANSACTION #{{ $eventTransaction->id }}
                </p>

                <h1>
                    Event Transaction Details
                </h1>

                <p class="admin-users-intro">
                    Review transaction information, workflow status and activity history.
                </p>

            </div>


            <div class="admin-partner-actions">

                @can('update', $eventTransaction)

                    <a href="{{ route('admin.event-transactions.edit', $eventTransaction) }}">
                        Edit
                    </a>

                @endcan

            </div>

        </div>


        {{-- Success Message --}}
        @if (session('status'))

            <div class="admin-users-success" role="status">
                {{ session('status') }}
            </div>

        @endif


        {{-- Error Message --}}
        @if (session('error'))

            <div class="admin-users-error" role="alert">
                {{ session('error') }}
            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- CURRENT STATUS --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-event-status-header">

                <div>

                    <p class="admin-auth-eyebrow">
                        CURRENT WORKFLOW
                    </p>

                    <h2>
                        Transaction Status
                    </h2>

                </div>

                <span class="admin-partner-status admin-partner-status--{{ $eventTransaction->review_status === 'approved' ? 'active' : ($eventTransaction->review_status === 'rejected' ? 'inactive' : 'pending') }}">
                    {{ $eventTransaction->review_status
                        ? ucfirst(str_replace('_', ' ', $eventTransaction->review_status))
                        : '—' }}
                </span>
            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- TRANSACTION INFORMATION --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        TRANSACTION
                    </p>

                    <h2>
                        Transaction Information
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Transaction ID</small>

                    <strong>
                        #{{ $eventTransaction->id }}
                    </strong>
                </div>


                <div>
                    <small>Workflow Status</small>
                    <span class="admin-partner-status admin-partner-status--{{ $eventTransaction->workflow_status === 'approved' ? 'active' : ($eventTransaction->workflow_status === 'rejected' ? 'inactive' : 'pending') }}">
                        {{ $eventTransaction->workflow_status
                            ? ucfirst(str_replace('_', ' ', $eventTransaction->workflow_status))
                            : '—' }}
                    </span>
                </div>



                <div>
                    <small>Review Status</small>
                    <span class="admin-partner-status admin-partner-status--{{ $eventTransaction->review_status === 'approved' ? 'active' : ($eventTransaction->review_status === 'rejected' ? 'inactive' : 'pending') }}">
                        {{ $eventTransaction->review_status
                            ? ucfirst(str_replace('_', ' ', $eventTransaction->review_status))
                            : '—' }}
                    </span>
                </div>


                <div>
                    <small>Event Value</small>

                    <strong>
                        @if ($eventTransaction->event_value !== null)
                            ₹{{ number_format((float) $eventTransaction->event_value, 2) }}
                        @else
                            —
                        @endif
                    </strong>
                </div>


                <div>
                    <small>Created At</small>

                    <strong>
                        {{ $eventTransaction->created_at?->format('d M Y, h:i A') ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Last Updated</small>

                    <strong>
                        {{ $eventTransaction->updated_at?->format('d M Y, h:i A') ?: '—' }}
                    </strong>
                </div>


                <div>

                </div>


                <div>
                    <small>Submitted Date</small>

                    <strong>
                        {{ $eventTransaction->event_date_submitted?->format('d M Y H:i A') ?: '—' }}
                    </strong>
                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- BENEFICIARY --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        BENEFICIARY
                    </p>

                    <h2>
                        Beneficiary Information
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Name</small>

                    <strong>
                        {{ $eventTransaction->beneficiary_name ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Mobile Number</small>

                    <strong>
                        {{ $eventTransaction->beneficiary_phone_number ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Learner ID</small>

                    <strong>
                        {{ $eventTransaction->learner_id ?: '—' }}
                    </strong>
                </div>


                @if ($eventTransaction->learner)

                    <div>
                        <small>Learner</small>

                        <strong>
                            {{ trim(
                                ($eventTransaction->learner->first_name ?? '') . ' ' .
                                ($eventTransaction->learner->last_name ?? '')
                            ) ?: '—' }}
                        </strong>
                    </div>

                @endif

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- EVENT INFORMATION --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        EVENT
                    </p>

                    <h2>
                        Event Information
                    </h2>

                </div>

            </div>



<div class="admin-event-details-grid">

    <div>
        <small>Event Master ID</small>
        <strong>
           {{ $eventTransaction->eventMaster?->event_name ?? '—' }}
        </strong>
    </div>



    <div>
        <small>Event Category</small>
        <strong>
            {{ $eventTransaction->eventCategory?->name ?? $eventTransaction->event_category_name ?? '—' }}
        </strong>
    </div>

    <div>
        <small>Field Type</small>
        <strong>
            {{ $eventTransaction->fieldType?->name ?? '—' }}
        </strong>
    </div>

    <div>
        <small>Industry Type</small>
        <strong>
            {{ $eventTransaction->industryType?->name ?? '—' }}
        </strong>
    </div>

</div>


        </section>


        {{-- ========================================================= --}}
        {{-- ASSIGNMENT --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        ASSIGNMENT
                    </p>

                    <h2>
                        Partner & Field Agent
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Partner</small>

                    <strong>
                        {{ $eventTransaction->partner?->name ?: '—' }}
                    </strong>

                    @if ($eventTransaction->partner_id)
                        <small>
                            ID: {{ $eventTransaction->partner_id }}
                        </small>
                    @endif
                </div>


                <div>
                    <small>Partner Center</small>

                    <strong>
                        {{ $eventTransaction->partnerCenter?->name ?: '—' }}
                    </strong>

                    @if ($eventTransaction->partner_center_id)
                        <small>
                            ID: {{ $eventTransaction->partner_center_id }}
                        </small>
                    @endif
                </div>


                <div>
                    <small>Partner Placement User</small>

                    <strong>
                        {{ $eventTransaction->partnerPlacementUser?->name ?: '—' }}
                    </strong>

                    @if ($eventTransaction->partner_placement_user_id)
                        <small>
                            ID: {{ $eventTransaction->partner_placement_user_id }}
                        </small>
                    @endif
                </div>


                <div>
                    <small>Field Agent</small>

                    @if ($eventTransaction->fieldAgent)

                        <strong>
                            {{ $eventTransaction->fieldAgent->name }}
                        </strong>

                        <small>
                            Sakhi ID:
                            {{ $eventTransaction->fieldAgent->sakhi_id }}
                        </small>

                    @else

                        <strong>
                            —
                        </strong>

                    @endif

                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- DOCUMENTS --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        DOCUMENTS
                    </p>

                    <h2>
                        Uploaded Documents
                    </h2>

                </div>

            </div>


@php
    $documentMapping = [
        'uploaded_doc_1' => 'Document 1',
        'uploaded_doc_2' => 'Document 2',
        'uploaded_doc_3' => 'Document 3',
    ];

    // Check individual document fields first.
    $hasIndividualDocuments = collect(array_keys($documentMapping))
        ->contains(function ($field) use ($eventTransaction) {
            return !empty($eventTransaction->{$field});
        });

    // Decode JSON only for the fallback case.
    $documentLinks = $eventTransaction->uploaded_doc_links;

    if (is_string($documentLinks)) {
        $documentLinks = json_decode($documentLinks, true);
    }

    $hasDocumentLinks = is_array($documentLinks)
        && !empty($documentLinks);

    $jsonDocumentMapping = [
        'document_doc_11' => 'Document 1',
        'document_doc_21' => 'Document 2',
        'document_doc_31' => 'Document 3',
    ];
@endphp

<div class="admin-event-documents">

    {{-- PRIORITY 1: Individual document columns --}}
    @if ($hasIndividualDocuments)

        @foreach ($documentMapping as $field => $label)

            @php
                $documentPath = $eventTransaction->{$field};

                $documentUrl = $documentPath
                    ? asset('storage/' . ltrim($documentPath, '/'))
                    : null;

                $extension = $documentPath
                    ? strtolower(pathinfo($documentPath, PATHINFO_EXTENSION))
                    : '';

                $isPdf = $extension === 'pdf';

                $isImage = in_array($extension, [
                    'jpg', 'jpeg', 'png', 'gif', 'webp'
                ]);
            @endphp

            <div class="admin-event-document">

                <div class="admin-document-header">
                    <span class="admin-document-label">
                        {{ $label }}
                    </span>
                </div>

                @if ($documentUrl)

                    <div class="admin-document-actions">

                        <button type="button"
                            class="admin-document-btn admin-document-view"
                            data-url="{{ $documentUrl }}"
                            data-type="{{ $isPdf ? 'pdf' : ($isImage ? 'image' : 'other') }}"
                            data-label="{{ $label }}">

                            @if ($isPdf)
                                <i class="fas fa-file-pdf"></i>
                            @elseif ($isImage)
                                <i class="fas fa-file-image"></i>
                            @else
                                <i class="fas fa-file-alt"></i>
                            @endif

                            <span>View</span>
                        </button>

                        <a href="{{ $documentUrl }}"
                           download="{{ basename($documentPath) }}"
                           class="admin-document-btn admin-document-download"
                           title="Download {{ $label }}">

                            <i class="fas fa-download"></i>
                            <span>Download</span>
                        </a>

                    </div>

                @else

                    <div class="admin-document-empty">
                        <i class="fas fa-minus-circle"></i>
                        <span>Not uploaded</span>
                    </div>

                @endif

            </div>

        @endforeach

    {{-- PRIORITY 2: JSON document links only if all individual fields are empty --}}
    @elseif ($hasDocumentLinks)

        @foreach ($jsonDocumentMapping as $jsonKey => $label)

            @php
                $documentPath = $documentLinks[$jsonKey] ?? null;

                $documentUrl = $documentPath
                    ? asset('storage/' . ltrim($documentPath, '/'))
                    : null;

                $extension = $documentPath
                    ? strtolower(pathinfo($documentPath, PATHINFO_EXTENSION))
                    : '';

                $isPdf = $extension === 'pdf';

                $isImage = in_array($extension, [
                    'jpg', 'jpeg', 'png', 'gif', 'webp'
                ]);
            @endphp

            <div class="admin-event-document">

                <div class="admin-document-header">
                    <span class="admin-document-label">
                        {{ $label }}
                    </span>
                </div>

                @if ($documentUrl)

                    <div class="admin-document-actions">

                        <button type="button"
                            class="admin-document-btn admin-document-view"
                            data-url="{{ $documentUrl }}"
                            data-type="{{ $isPdf ? 'pdf' : ($isImage ? 'image' : 'other') }}"
                            data-label="{{ $label }}">

                            @if ($isPdf)
                                <i class="fas fa-file-pdf"></i>
                            @elseif ($isImage)
                                <i class="fas fa-file-image"></i>
                            @else
                                <i class="fas fa-file-alt"></i>
                            @endif

                            <span>View</span>
                        </button>

                        <a href="{{ $documentUrl }}"
                           download="{{ basename($documentPath) }}"
                           class="admin-document-btn admin-document-download"
                           title="Download {{ $label }}">

                            <i class="fas fa-download"></i>
                            <span>Download</span>
                        </a>

                    </div>

                @else

                    <div class="admin-document-empty">
                        <i class="fas fa-minus-circle"></i>
                        <span>Not uploaded</span>
                    </div>

                @endif

            </div>

        @endforeach

    @else

        <div class="admin-document-empty">
            <i class="fas fa-minus-circle"></i>
            <span>No documents uploaded</span>
        </div>

    @endif

</div>


</div>

        </section>

        {{-- ========================================================= --}}
        {{-- LAST COMMENT --}}
        {{-- ========================================================= --}}

        @if ($eventTransaction->last_comment)

            <section class="admin-partners-panel">

                <div class="admin-partners-heading">

                    <div>

                        <p class="admin-auth-eyebrow">
                            COMMENT
                        </p>

                        <h2>
                            Latest Comment
                        </h2>

                    </div>

                </div>


                <div style="padding:10px;">
                    {{ $eventTransaction->last_comment }}
                </div>

            </section>

        @endif


        {{-- ========================================================= --}}
        {{-- ADMIN WORKFLOW ACTIONS --}}
        {{-- ========================================================= --}}

         @if (session('success'))
                    <div class="event-success-alert" role="alert">
                        <span class="event-success-alert__icon">
                            &#10003;
                        </span>

                        <div class="event-success-alert__content">
                            <strong>Success!</strong>
                            <p>{{ session('success') }}</p>
                        </div>

                        <button type="button"
                                class="event-success-alert__close"
                                onclick="this.parentElement.remove()"
                                aria-label="Close">
                            &times;
                        </button>
                    </div>
                @endif

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        ADMIN ACTIONS
                    </p>

                    <h2>
                        Review Transaction
                    </h2>

                </div>

            </div>






            <div class="admin-event-actions">


                {{-- Submitted / Resubmitted → Under Review --}}
                @if (in_array($workflowStatus, ['submitted', 'resubmitted']))

                    @can('underReview', $eventTransaction)

                        <form method="POST"
                            action="{{ route('admin.event-transactions.under-review', $eventTransaction) }}">

                            @csrf

                            <button type="submit"
                                onclick="return confirm('Move this transaction to Under Review?')">

                                Move to Under Review

                            </button>

                        </form>

                    @endcan

                @endif


                {{-- Under Review → Approve / Reject --}}
                @if ($workflowStatus === 'under_review')

                    @can('approve', $eventTransaction)

                        <form method="POST"
                            action="{{ route('admin.event-transactions.approve', $eventTransaction) }}"
                            onsubmit="return confirm('Are you sure you want to approve this transaction?')">

                            @csrf

                            <button type="submit">
                                Approve
                            </button>

                        </form>

                    @endcan


                    @can('reject', $eventTransaction)

                        <details>

                            <summary>
                                Reject Transaction
                            </summary>


                            <form method="POST"
                                action="{{ route('admin.event-transactions.reject', $eventTransaction) }}"
                                style="margin-top:15px;">

                                @csrf


                                <label>
                                    <span class="visually-hidden">
                                        Rejection reason
                                    </span>

                                    <input type="text"
                                        name="comment"
                                        required
                                        placeholder="Enter comment ..."/>
                                </label>


                                <button type="submit">
                                    Confirm Rejection
                                </button>

                            </form>

                        </details>

                    @endcan

                @endif


                <div id="eventTransactionEditForm" class="et-edit-form" style="display:none;">

                    <form method="POST"
                        action="{{ route('admin.event-transactions.update', $eventTransaction) }}"
                        class="et-edit-form__body">

                        @csrf
                        @method('PUT')

                        <div class="et-form-field">
                            <label for="review_status">Event Review Status</label>
                            @use('App\Support\EventTransactionReviewStatus')
                            <select name="review_status">
                                <option value="">All Review Statuses</option>

                                @foreach(EventTransactionReviewStatus::options() as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(request('review_status') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('review_status')
                                <small class="et-form-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="et-form-field">
                            <label for="field_type">Field Type</label>

                            <select name="field_type" id="field_type" required>
                                <option value="">Select Field Type</option>

                                @foreach ($fieldTypes as $type)
                                    <option value="{{ $type->id }}"
                                        @selected(old('field_type', $eventTransaction->field_type_id) == $type->id)>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('field_type')
                                <small class="et-form-error">{{ $message }}</small>
                            @enderror
                        </div>

                       <div class="et-form-field">
                        <label for="industry_type">Industry Type</label>

                        <select name="industry_type" id="industry_type" required>
                            <option value="">Select Industry Type</option>

                            @foreach ($industryTypes as $industry)
                                <option value="{{ $industry->id }}"
                                    @selected(old('industry_type', $eventTransaction->industry_type_id) == $industry->id)>
                                    {{ $industry->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('industry_type')
                            <small class="et-form-error">{{ $message }}</small>
                        @enderror
                    </div>

                        <div class="et-form-field">
                            <label for="comment_type">Comment Type</label>
                            <select name="comment_type" id="comment_type" required>
                                <option value="">Select Comment Type</option>
                                <option value="Internal" @selected(old('comment_type') === 'Internal')>Internal</option>
                                <option value="External" @selected(old('comment_type') === 'External')>External</option>
                            </select>
                            @error('comment_type')
                                <small class="et-form-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="et-form-field">
                            <label for="comment">Comment</label>
                            <input type="text" style="width: 100%"
                                name="comment"
                                id="comment"
                                value="{{ old('comment') }}"
                                placeholder="Enter your comment..."
                                required>
                            @error('comment')
                                <small class="et-form-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="et-form-actions">
                            <button type="button" id="cancelEventTransactionForm" class="et-btn-cancel">
                                Cancel
                            </button>
                            <button type="submit" class="field-agent-btn field-agent-btn-primary">
                                Update Event Transaction
                            </button>
                        </div>
                    </form>
                </div>



               {{-- Edit --}}
             @can('update', $eventTransaction)
                <button type="button"
                        id="toggleEventTransactionForm"
                        class="btn-primary">
                    Edit Event Transaction
                </button>
            @endcan

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- ACTIVITY TIMELINE --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        AUDIT TRAIL
                    </p>

                    <h2>
                        Activity Timeline
                    </h2>

                </div>

            </div>


            @if ($eventTransaction->logs->count())

                <div class="admin-event-timeline">

                    @foreach ($eventTransaction->logs->sortByDesc('created_at') as $log)


                    <article class="admin-event-timeline-item">
                        <div>
                            <strong>
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </strong>

                            @if ($log->old_status || $log->new_status)
                            @php
                                $statusColors = [
                                    'draft'        => 'warning',
                                    'under_review' => 'warning',
                                    'pending'      => 'warning',
                                    'approved'     => 'success',
                                    'accepted'     => 'success',
                                    'rejected'     => 'danger',
                                    'returned'     => 'danger',
                                    'completed'    => 'primary',
                                    'open'         => 'secondary',
                                ];

                                $oldStatus = strtolower($log->old_status ?? '');
                                $newStatus = strtolower($log->new_status ?? '');

                                $oldColor = $statusColors[$oldStatus] ?? 'secondary';
                                $newColor = $statusColors[$newStatus] ?? 'secondary';

                                $formatStatus = fn ($status) => $status
                                    ? ucwords(str_replace('_', ' ', $status))
                                    : 'Not Set';
                            @endphp

                            <div class="status-transition">
                                <span class="status-badge status-{{ $oldColor }}">
                                    {{ $formatStatus($oldStatus) }}
                                </span>

                                <span class="status-arrow" aria-label="changed to">
                                    <i class="fas fa-long-arrow-alt-right"></i>
                                </span>

                                <span class="status-badge status-{{ $newColor }}">
                                    {{ $formatStatus($newStatus) }}
                                </span>
                            </div>

                            @endif

                            <small>
                                Event transaction updated by
                                {{ $log->user_type ?: 'Unknown User' }}
                            </small>

                            @if (filled($log->comment))
                                <p>{!! $log->comment !!}</p>
                            @endif
                        </div>

                        <div>
                            <small>
                                {{ $log->created_at?->format('d M Y, h:i A') }}
                            </small>

                            <br>

                            <small class="text-muted">
                                @if (filled($log->actor_name))
                                    Updated By: {{ $log->actor_name }}
                                @elseif (filled($log->user_id))
                                    User ID: {{ $log->user_id }}
                                @else
                                    Unknown User
                                @endif

                                @if (filled($log->user_type))
                                    ({{ $log->user_type }})
                                @endif
                            </small>
                        </div>
                    </article>


                    @endforeach

                </div>

            @else

                <p style="padding: 10px;">
                    No activity recorded yet.
                </p>

            @endif

        </section>


    </section>

</main>

<style>

    .document-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.document-modal.is-open {
    display: flex;
}

.document-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(3px);
}

.document-modal__dialog {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    width: min(100%, 1000px);
    height: min(90vh, 800px);
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
}

.document-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px;
    border-bottom: 1px solid #e5e7eb;
}

.document-modal__header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
}

.document-modal__header p {
    margin: 5px 0 0;
    font-size: 13px;
    color: #6b7280;
}

.document-modal__close {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 8px;
    background: #f3f4f6;
    color: #374151;
    font-size: 28px;
    cursor: pointer;
}

.document-modal__body {
    flex: 1;
    min-height: 0;
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    background: #f3f4f6;
}

.document-modal__body iframe {
    width: 100%;
    height: 100%;
    min-height: 400px;
    border: 0;
    background: #fff;
}

.document-modal__body img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.document-modal__message {
    padding: 24px;
    text-align: center;
    color: #374151;
}

.document-modal__footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    padding: 14px 22px;
    border-top: 1px solid #e5e7eb;
    background: #fff;
}

@media (max-width: 600px) {
    .document-modal {
        padding: 8px;
    }

    .document-modal__dialog {
        height: 94vh;
        border-radius: 10px;
    }

    .document-modal__header,
    .document-modal__footer {
        padding: 12px;
    }
}

</style>

{{-- Document preview modal: keep this OUTSIDE the document loops/conditions --}}
<div id="docModal"
     class="document-modal"
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-labelledby="docTitle">
    <div class="document-modal__backdrop" data-doc-close></div>

    <div class="document-modal__dialog">
        <div class="document-modal__header">
            <div>
                <h3 id="docTitle">View Document</h3>
                <p id="docSubtitle">Document preview</p>
            </div>
            <button type="button"
                    id="docClose"
                    class="document-modal__close"
                    aria-label="Close document preview">&times;</button>
        </div>

        <div id="docBody" class="document-modal__body">
            <p class="document-modal__message">Select a document to preview.</p>
        </div>

        <div class="document-modal__footer">
            <a id="docOpenNewTab" href="#" target="_blank" rel="noopener noreferrer"
               class="admin-document-btn admin-document-download">Open in new tab</a>
            <button type="button" class="admin-document-btn admin-document-view" data-doc-close>
                Close
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function initEventTransactionPage() {
        // Edit form toggle
        const toggleButton = document.getElementById('toggleEventTransactionForm');
        const editForm = document.getElementById('eventTransactionEditForm');
        const cancelButton = document.getElementById('cancelEventTransactionForm');

        if (toggleButton && editForm && !toggleButton.dataset.bound) {
            toggleButton.dataset.bound = '1';

            toggleButton.addEventListener('click', function () {
                const isHidden = window.getComputedStyle(editForm).display === 'none';
                editForm.style.display = isHidden ? 'block' : 'none';
                toggleButton.textContent = isHidden ? 'Hide Edit Form' : 'Edit Event Transaction';

                if (isHidden) {
                    editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            if (cancelButton) {
                cancelButton.addEventListener('click', function () {
                    editForm.style.display = 'none';
                    toggleButton.textContent = 'Edit Event Transaction';
                });
            }

            @if ($errors->any())
                editForm.style.display = 'block';
                toggleButton.textContent = 'Hide Edit Form';
            @endif
        }

        // Document preview modal
        const modal = document.getElementById('docModal');
        const title = document.getElementById('docTitle');
        const subtitle = document.getElementById('docSubtitle');
        const body = document.getElementById('docBody');
        const openNewTab = document.getElementById('docOpenNewTab');
        const closeButton = document.getElementById('docClose');

        if (!modal || !title || !body) {
            console.error('Document preview modal markup is missing.');
            return;
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            body.replaceChildren();
            body.innerHTML = '<p class="document-modal__message">Select a document to preview.</p>';
            if (openNewTab) {
                openNewTab.href = '#';
                openNewTab.style.display = 'none';
            }
            document.body.style.overflow = '';
        }

        function openModal(url, type, label) {
            if (!url) {
                alert('Document URL is missing.');
                return;
            }

            title.textContent = label || 'View Document';
            subtitle.textContent = type === 'pdf' ? 'PDF document' :
                (type === 'image' ? 'Image preview' : 'File preview');
            body.replaceChildren();

            if (openNewTab) {
                openNewTab.href = url;
                openNewTab.style.display = 'inline-flex';
            }

            if (type === 'pdf') {
                const iframe = document.createElement('iframe');
                iframe.title = label || 'PDF document preview';
                iframe.src = url;
                iframe.setAttribute('loading', 'eager');
                body.appendChild(iframe);
            } else if (type === 'image') {
                const img = document.createElement('img');
                img.src = url;
                img.alt = label || 'Uploaded document';
                img.onerror = function () {
                    body.replaceChildren();
                    const message = document.createElement('p');
                    message.className = 'document-modal__message';
                    message.textContent = 'Image preview could not be loaded. Use “Open in new tab” to view the file.';
                    body.appendChild(message);
                };
                body.appendChild(img);
            } else {
                const message = document.createElement('p');
                message.className = 'document-modal__message';
                message.textContent = 'Preview is not available for this file type. Use “Open in new tab” to view it.';
                body.appendChild(message);
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        if (!document.documentElement.dataset.documentModalBound) {
            document.documentElement.dataset.documentModalBound = '1';

            document.addEventListener('click', function (event) {
                const viewButton = event.target.closest('.admin-document-view[data-url]');
                if (viewButton) {
                    event.preventDefault();
                    openModal(
                        viewButton.dataset.url,
                        viewButton.dataset.type || 'other',
                        viewButton.dataset.label || 'View Document'
                    );
                    return;
                }

                if (event.target.closest('#docClose, [data-doc-close]')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventTransactionPage, { once: true });
    } else {
        initEventTransactionPage();
    }
})();
</script>

@endsection
