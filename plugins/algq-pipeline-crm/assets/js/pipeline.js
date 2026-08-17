(() => {
  'use strict';
  const board = document.querySelector('.algq-board');
  if (!board || board.dataset.canTransition !== '1' || typeof ALGQPipeline === 'undefined') return;
  let dragged = null;
  board.addEventListener('dragstart', (event) => {
    const card = event.target.closest('.algq-deal-card');
    if (!card) return;
    dragged = card;
    card.classList.add('is-dragging');
    event.dataTransfer.effectAllowed = 'move';
  });
  board.addEventListener('dragend', () => {
    if (dragged) dragged.classList.remove('is-dragging');
    dragged = null;
    board.querySelectorAll('.is-over').forEach((node) => node.classList.remove('is-over'));
  });
  board.addEventListener('dragover', (event) => {
    const zone = event.target.closest('.algq-dropzone');
    if (!zone || !dragged) return;
    event.preventDefault();
    zone.classList.add('is-over');
  });
  board.addEventListener('dragleave', (event) => {
    const zone = event.target.closest('.algq-dropzone');
    if (zone) zone.classList.remove('is-over');
  });
  board.addEventListener('drop', async (event) => {
    const zone = event.target.closest('.algq-dropzone');
    const stage = zone?.closest('.algq-stage')?.dataset.stage;
    if (!zone || !stage || !dragged) return;
    event.preventDefault();
    zone.classList.remove('is-over');
    const card = dragged;
    const originalZone = card.parentElement;
    zone.appendChild(card);
    try {
      const response = await fetch(`${ALGQPipeline.root}/deals/${card.dataset.id}/stage`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ALGQPipeline.nonce },
        body: JSON.stringify({ stage, record_version: Number(card.dataset.version), reason: 'kanban_drag' })
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Stage update failed.');
      card.dataset.version = data.record_version;
      window.location.reload();
    } catch (error) {
      originalZone.appendChild(card);
      window.alert(error.message || ALGQPipeline.conflictMessage);
    }
  });
})();
