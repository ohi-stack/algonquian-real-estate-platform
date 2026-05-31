(function(){
  'use strict';

  function qs(selector, scope){ return (scope || document).querySelector(selector); }

  function setButtonState(button, loading){
    if(!button){ return; }
    button.disabled = !!loading;
    if(loading){
      button.setAttribute('data-original-label', button.textContent || '');
      button.textContent = 'Updating...';
    } else if(button.getAttribute('data-original-label')){
      button.textContent = button.getAttribute('data-original-label');
      button.removeAttribute('data-original-label');
    }
  }

  function updateProgressBar(container, percentage){
    var bar = qs('.algq-progress span', container || document);
    if(bar){ bar.style.width = String(percentage || 0) + '%'; }
  }

  function postProgress(button){
    var lesson = button.closest('.algq-lesson-single');
    if(!lesson || !window.algqEducation){ return; }

    var courseId = lesson.getAttribute('data-course-id');
    var lessonId = lesson.getAttribute('data-lesson-id');
    var isComplete = button.getAttribute('data-status') === 'complete';
    var action = isComplete ? 'algq_mark_lesson_incomplete' : 'algq_mark_lesson_complete';

    var body = new URLSearchParams();
    body.append('action', action);
    body.append('nonce', window.algqEducation.nonce || '');
    body.append('course_id', courseId || '0');
    body.append('lesson_id', lessonId || '0');

    setButtonState(button, true);

    fetch(window.algqEducation.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: body.toString()
    })
    .then(function(response){ return response.json(); })
    .then(function(payload){
      if(!payload || !payload.success){ throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Progress update failed.'); }
      var nowComplete = !isComplete;
      button.setAttribute('data-status', nowComplete ? 'complete' : 'incomplete');
      button.textContent = nowComplete ? 'Mark Incomplete' : 'Mark Complete';
      button.removeAttribute('data-original-label');
      updateProgressBar(document, payload.data && payload.data.percentage ? payload.data.percentage : 0);
      lesson.dispatchEvent(new CustomEvent('algqEducationProgressUpdated', {detail: payload.data || {}}));
    })
    .catch(function(error){
      window.alert(error.message || 'Progress update failed.');
    })
    .finally(function(){ setButtonState(button, false); });
  }

  document.addEventListener('click', function(event){
    var button = event.target.closest('.algq-complete-lesson');
    if(button){
      event.preventDefault();
      postProgress(button);
    }
  });
})();
