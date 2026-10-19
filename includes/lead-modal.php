<div class="modal" id="leadModal" role="dialog" aria-modal="true" aria-labelledby="leadModalTitle" aria-hidden="true">
  <div class="modal__backdrop" data-close></div>
  <div class="modal__panel">
    <button class="modal__close" data-close aria-label="Close">&times;</button>
    <div class="modal__media" id="leadModalMedia"></div>
    <div class="modal__body">
      <p class="eyebrow" id="leadModalEyebrow">Private advisory</p>
      <h3 class="modal__title" id="leadModalTitle">Enquire now</h3>
      <p class="modal__text" id="leadModalText">Share your details and a dedicated advisor will reach out within 30 minutes.</p>
      <?php lead_form(['source' => 'popup', 'button' => 'Submit', 'id' => 'modal']); ?>
      <ul class="modal__perks">
        <li>Best developer price</li><li>Free site visit cab</li><li>Zero brokerage</li>
      </ul>
    </div>
  </div>
</div>
