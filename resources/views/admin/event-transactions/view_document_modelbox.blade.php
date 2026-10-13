<div id="documentPreviewModal" class="document-modal"
     aria-hidden="true">

    <div class="document-modal__backdrop"
         onclick="closeDocumentModal()"></div>

    <div class="document-modal__dialog"
         role="dialog"
         aria-modal="true"
         aria-labelledby="documentModalTitle">

        <div class="document-modal__header">
            <div>
                <h3 id="documentModalTitle">View Document</h3>
                <p id="documentModalSubtitle">Document preview</p>
            </div>

            <button type="button"
                    class="document-modal__close"
                    onclick="closeDocumentModal()"
                    aria-label="Close modal">
                &times;
            </button>
        </div>

        <div id="documentModalBody"
             class="document-modal__body">
        </div>

        <div class="document-modal__footer">
            <a id="documentModalDownload"
               href="#"
               class="admin-document-btn admin-document-download"
               download>
                <i class="fas fa-download"></i>
                Download
            </a>

            <button type="button"
                    class="admin-document-btn"
                    onclick="closeDocumentModal()">
                Close
            </button>
        </div>
    </div>
</div>
