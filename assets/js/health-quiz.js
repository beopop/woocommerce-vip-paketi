// Debug: Log when health quiz JS loads
console.log('WVP Health Quiz JavaScript loaded at', new Date());

// Body Map Interactivity
function initializeBodyMap() {
  const bodyRegions = document.querySelectorAll('.clickable-organ');
  const analysisPanel = document.getElementById('ai-analysis-panel');
  const panelTitle = document.getElementById('panel-region-title');
  const symptomsSection = document.getElementById('symptoms-section');
  const causesSection = document.getElementById('causes-section');
  const solutionsSection = document.getElementById('solutions-section');
  const recommendationSection = document.getElementById('ai-recommendation');
  const symptomsText = document.getElementById('symptoms-text');
  const causesText = document.getElementById('causes-text');
  const solutionsText = document.getElementById('solutions-text');

  if (!bodyRegions.length || !analysisPanel) return;

  bodyRegions.forEach(region => {
    region.addEventListener('click', function() {
      // Remove active class from all regions
      bodyRegions.forEach(r => r.classList.remove('active'));

      // Add active class to clicked region
      this.classList.add('active');

      // Get data from the clicked region
      const title = this.dataset.title;
      const symptoms = this.dataset.symptoms;
      const causes = this.dataset.causes;
      const solutions = this.dataset.solutions;

      // Update panel content
      if (panelTitle) panelTitle.textContent = title;
      if (symptomsText) symptomsText.textContent = symptoms;
      if (causesText) causesText.textContent = causes;
      if (solutionsText) solutionsText.textContent = solutions;

      // Show sections with animation delay
      setTimeout(() => {
        if (symptomsSection) symptomsSection.style.display = 'block';
      }, 100);

      setTimeout(() => {
        if (causesSection) causesSection.style.display = 'block';
      }, 200);

      setTimeout(() => {
        if (solutionsSection) solutionsSection.style.display = 'block';
      }, 300);

      setTimeout(() => {
        if (recommendationSection) recommendationSection.style.display = 'block';
      }, 400);

      // Add visual feedback
      this.style.filter = 'url(#glow)';

      // Animate the AI pulse
      const aiPulse = document.querySelector('.ai-pulse');
      if (aiPulse) {
        aiPulse.style.animation = 'none';
        setTimeout(() => {
          aiPulse.style.animation = 'ai-pulse-animation 1s ease-in-out infinite';
        }, 100);
      }
    });

    // Hover effects
    region.addEventListener('mouseenter', function() {
      if (!this.classList.contains('active')) {
        this.style.opacity = '0.6';
        this.style.transform = 'scale(1.05)';
      }
    });

    region.addEventListener('mouseleave', function() {
      if (!this.classList.contains('active')) {
        this.style.opacity = '0.0';
        this.style.transform = 'scale(1)';
        this.style.filter = 'none';
      }
    });
  });
}

function closeAnalysisPanel() {
  const bodyRegions = document.querySelectorAll('.clickable-organ');
  const symptomsSection = document.getElementById('symptoms-section');
  const causesSection = document.getElementById('causes-section');
  const solutionsSection = document.getElementById('solutions-section');
  const recommendationSection = document.getElementById('ai-recommendation');
  const panelTitle = document.getElementById('panel-region-title');

  // Remove active class from all regions
  bodyRegions.forEach(region => {
    region.classList.remove('active');
    region.style.opacity = '0.0';
    region.style.transform = 'scale(1)';
    region.style.filter = 'none';
  });

  // Hide sections
  if (symptomsSection) symptomsSection.style.display = 'none';
  if (causesSection) causesSection.style.display = 'none';
  if (solutionsSection) solutionsSection.style.display = 'none';
  if (recommendationSection) recommendationSection.style.display = 'none';

  // Reset panel title
  if (panelTitle) panelTitle.textContent = 'Izaberite region za analizu';
}

document.addEventListener('DOMContentLoaded',function(){
  // Initialize body map if it exists
  initializeBodyMap();

  const quiz=document.getElementById('wvp-health-quiz');
  if(!quiz) return;
  const steps=Array.from(quiz.querySelectorAll('.wvp-health-step'));
  let resultId=null;
  let saveAnswersPromise=null;
  let sessionId=null;
  const STORAGE_KEY='wvp_health_quiz_state';
  const SESSION_KEY='wvp_health_quiz_session_id';
  let currentStep = wvpHealthData.initial_step || 0;

  // Initialize or restore session ID
  function initializeSession() {
    sessionId = localStorage.getItem(SESSION_KEY);
    if (!sessionId) {
      // Generate new session ID (similar to UUID v4)
      sessionId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
      });
      localStorage.setItem(SESSION_KEY, sessionId);
      console.log('🔄 Generated new session ID:', sessionId);
    } else {
      console.log('🔄 Restored session ID:', sessionId);
    }
  }
  const baseUrl=wvpHealthData.base_url || window.location.href.replace(/\/?zavrsena-anketa\/?$/,'');
  const debugMode=!!wvpHealthData.debug;
  const debugContainer=document.getElementById('wvp-health-debug-container');
  const debugToggle=document.getElementById('wvp-health-debug-toggle');
  const debugLog=document.getElementById('wvp-health-debug-log');
  const noteBox=document.getElementById('wvp-health-note');
  const explBox=document.getElementById('wvp-health-explanations');
  const statusBox=document.getElementById('wvp-health-status');
  // Old gauge elements removed - now using AI analysis on completed page
  if(debugToggle){
    debugToggle.addEventListener('change',()=>{debugLog.style.display=debugToggle.checked?'block':'none';});
  }
  function showDebug(log){
    if(!debugMode||!debugContainer) return;
    debugContainer.style.display='block';
    debugLog.textContent=log;
  }
  function updateNote(text){
    if(!noteBox) return;
    if(text){
      noteBox.innerHTML=text;
      noteBox.style.display='block';
    }else{
      noteBox.style.display='none';
    }
  }
  function updateProductInfo(type,id){
    const btn=quiz.querySelector('.wvp-health-select[data-type="'+type+'"]');
    if(!btn) return;
    if(!id || !wvpHealthData.products || !wvpHealthData.products[id]){
      btn.style.display='none';
      return;
    }
    btn.style.display='flex';
    btn.dataset.product=id;
    const info=wvpHealthData.products[id];
    const img=btn.querySelector('img');
    if(img){
      if(info.img){img.src=info.img;img.style.display='';}else{img.style.display='none';}
    }
    const price=btn.querySelector('.wvp-health-price');
    if(price){
      let html=info.price||'';
      html=html.trim();
      price.innerHTML=html;
    }
    const nameEl=btn.querySelector('.wvp-health-name');
    if(nameEl) nameEl.textContent=info.name;
  }
  function updateExplanations(html){
    if(!explBox) return;
    if(html){
      const first=document.getElementById('wvp-first-name').value.trim();
      const last=document.getElementById('wvp-last-name').value.trim();
      const name=(first||last)?(first+' '+last).trim():'';
      const greeting=name?`Poštovani/a ${name}`:'Poštovani/a';
      explBox.innerHTML=`<span class="wvp-health-greeting">${greeting}</span>`+'<br>'+html;
      explBox.style.display='block';
    }else{
      explBox.style.display='none';
    }
  }
  // Old updateStatus function removed - now using AI analysis on completed page

  const allProducts=new Set();
  wvpHealthData.questions.forEach(q=>{
    if(q.main) allProducts.add(String(q.main));
    if(q.extra) allProducts.add(String(q.extra));
    if(q.package) allProducts.add(String(q.package));
  });

  function applyResults(){
    const yesQuestions=[];
    let totalScore = 0;
    quiz.querySelectorAll('.wvp-health-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(sel&&sel.value.toLowerCase()==='da'){
        const questionIndex = parseInt(g.dataset.question);
        yesQuestions.push(questionIndex);

        // Calculate intensity-based score
        const intensityGroup = g.querySelector('.wvp-health-intensity-group');
        let intensityScore = 1; // Default score for "Da" answer
        if(intensityGroup && intensityGroup.style.display !== 'none'){
          const selectedIntensity = intensityGroup.querySelector('input:checked');
          if(selectedIntensity){
            intensityScore = parseInt(selectedIntensity.dataset.intensity) || 1;
          }
        }
        totalScore += intensityScore;
      }
    });

    const count={main:{},extra:{},package:{}};
    const notes=[];
    const mentioned=new Set();
    yesQuestions.forEach(i=>{
      const q=wvpHealthData.questions[i];
      if(!q) return;

      // Get intensity multiplier for this question
      const questionGroup = quiz.querySelector('.wvp-health-question-group[data-question="'+i+'"]');
      let intensityMultiplier = 1;
      if(questionGroup){
        const intensityGroup = questionGroup.querySelector('.wvp-health-intensity-group');
        if(intensityGroup && intensityGroup.style.display !== 'none'){
          const selectedIntensity = intensityGroup.querySelector('input:checked');
          if(selectedIntensity){
            intensityMultiplier = parseInt(selectedIntensity.dataset.intensity) || 1;
          }
        }
      }

      if(q.main) count.main[q.main]=(count.main[q.main]||0)+intensityMultiplier;
      if(q.extra) count.extra[q.extra]=(count.extra[q.extra]||0)+intensityMultiplier;
      if(q.package) count.package[q.package]=(count.package[q.package]||0)+intensityMultiplier;
      if(q.main) mentioned.add(String(q.main));
      if(q.extra) mentioned.add(String(q.extra));
      if(q.package) mentioned.add(String(q.package));
      if(q.note) notes.push(q.note);
    });
    function top(obj){let k=null,m=0;Object.keys(obj).forEach(key=>{if(obj[key]>m){m=obj[key];k=key;}});return k;}
    let main=top(count.main)||'';
    let extra=top(count.extra)||'';
    let pack=top(count.package)||'';
    let universal='';
    if(wvpHealthData.universal){
      const allMentioned=[...allProducts].every(p=>mentioned.has(String(p)));
      if(allMentioned){
        universal=wvpHealthData.universal;
      }
    }
    updateProductInfo('main',main);
    updateProductInfo('extra',extra);
    updateProductInfo('package',pack);
    updateProductInfo('universal',universal);
    updateNote('');
    updateExplanations(notes.join('<br>'));
    // updateStatus removed - now using AI analysis on completed page
  }
  function saveState(){
    try{
      const state={
        step:currentStep,
        resultId:resultId,
        first_name:document.getElementById('wvp-first-name').value,
        last_name:document.getElementById('wvp-last-name').value,
        email:document.getElementById('wvp-email').value,
        phone:document.getElementById('wvp-phone').value,
        year:document.getElementById('wvp-year').value,
        location:document.getElementById('wvp-location').value,
        country:document.getElementById('wvp-country').value,
        answers:{},
        intensities:{}
      };
      quiz.querySelectorAll('.wvp-health-question-group').forEach(g=>{
        const sel=g.querySelector('input:checked');
        if(sel) state.answers[g.dataset.question]=sel.dataset.index;

        // Save intensity selection
        const intensityGroup = g.querySelector('.wvp-health-intensity-group');
        if(intensityGroup){
          const intensitySel = intensityGroup.querySelector('input:checked');
          if(intensitySel) state.intensities[g.dataset.question]=intensitySel.dataset.intensity;
        }
      });
      localStorage.setItem(STORAGE_KEY,JSON.stringify(state));

      // Auto-save to database if we have basic info
      autoSaveToDatabase();
    }catch(e){}
  }

  // Optimized auto-save to database function
  function autoSaveToDatabase() {
      const saveStartTime = Date.now();

      // Get form data
      const firstName = document.getElementById('wvp-first-name')?.value?.trim() || '';
      const lastName = document.getElementById('wvp-last-name')?.value?.trim() || '';
      const email = document.getElementById('wvp-email')?.value?.trim() || '';

      // Get current answers directly from DOM - more reliable
      const currentAnswers = {};
      const currentIntensities = {};

      // Gather answers from all checked radio buttons
      document.querySelectorAll('.wvp-health-question-group input[type="radio"]:checked').forEach(radio => {
        const questionGroup = radio.closest('.wvp-health-question-group');
        if (questionGroup && questionGroup.dataset.question !== undefined) {
          const questionIndex = questionGroup.dataset.question;
          currentAnswers[questionIndex] = radio.value;
        }
      });

      // Gather intensities from all checked intensity radio buttons
      document.querySelectorAll('.wvp-health-intensity-group input[type="radio"]:checked').forEach(radio => {
        const intensityGroup = radio.closest('.wvp-health-intensity-group');
        if (intensityGroup && intensityGroup.dataset.question !== undefined) {
          const questionIndex = intensityGroup.dataset.question;
          currentIntensities[questionIndex] = radio.value;
        }
      });

      // Decide if we should save
      const hasFormData = firstName && lastName;
      const hasQuizData = Object.keys(currentAnswers).length > 0;

      if (hasFormData || hasQuizData) {
        console.log('💾 Auto-saving...', {
          formData: hasFormData,
          questionsAnswered: Object.keys(currentAnswers).length,
          intensitiesSet: Object.keys(currentIntensities).length,
          sessionId: sessionId
        });

        const data = new FormData();
        data.append('action', 'bulletproof_save_answers');
        data.append('nonce', wvpHealthData.nonce);
        data.append('first_name', firstName);
        data.append('last_name', lastName);
        data.append('email', email);
        data.append('phone', document.getElementById('wvp-phone')?.value?.trim() || '');
        data.append('birth_year', document.getElementById('wvp-year')?.value || '1990');
        data.append('location', document.getElementById('wvp-location')?.value?.trim() || '');
        data.append('country', document.getElementById('wvp-country')?.value?.trim() || '');

        // Send current answers and intensities in JSON format
        data.append('answers_data', JSON.stringify(currentAnswers));
        data.append('intensities_data', JSON.stringify(currentIntensities));
        data.append('auto_save', '1');
        data.append('session_id', sessionId);

        if (resultId) {
          data.append('result_id', resultId);
        }

        // Enhanced fetch with better error handling
        fetch(wvpHealthData.ajaxurl, {
          method: 'POST',
          body: data,
          credentials: 'same-origin'
        })
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
          })
          .then(res => {
            const saveTime = Date.now() - saveStartTime;

            if (res.success) {
              // Update result ID if we got a new one
              if (res.data.result_id && !resultId) {
                resultId = res.data.result_id;
                localStorage.setItem('wvp_health_quiz_result_id', resultId);
              }

              // Update session ID
              if (res.data.session_id) {
                sessionId = res.data.session_id;
                localStorage.setItem(SESSION_KEY, sessionId);
              }

              console.log(`✅ Auto-saved in ${saveTime}ms:`, {
                resultId: resultId,
                action: res.data.action,
                answers: Object.keys(currentAnswers).length,
                intensities: Object.keys(currentIntensities).length
              });

              // Show brief success indicator
              showSaveStatus('saved');
            } else {
              console.error('❌ Auto-save failed:', res.data?.message || 'Unknown error');
              showSaveStatus('error');
            }
          })
          .catch(err => {
            console.error('❌ Auto-save network error:', err);
            showSaveStatus('error');
          });
      } else {
        console.log('⏸️ Auto-save skipped: insufficient data', {
          hasFormData: hasFormData,
          hasQuizData: hasQuizData,
          firstName: !!firstName,
          email: !!email
        });
      }
  }

  // Visual save status indicator
  function showSaveStatus(status) {
    let indicator = document.getElementById('wvp-save-status');

    if (!indicator) {
      indicator = document.createElement('div');
      indicator.id = 'wvp-save-status';
      indicator.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 8px 16px;
        border-radius: 20px;
        color: white;
        font-size: 12px;
        font-weight: 600;
        z-index: 9999;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s ease;
      `;
      document.body.appendChild(indicator);
    }

    if (status === 'saved') {
      indicator.textContent = '✅ Sačuvano';
      indicator.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
    } else if (status === 'error') {
      indicator.textContent = '❌ Greška';
      indicator.style.background = 'linear-gradient(135deg, #dc3545, #e74c3c)';
    }

    // Show indicator
    indicator.style.opacity = '1';
    indicator.style.transform = 'translateX(0)';

    // Hide after 2 seconds
    setTimeout(() => {
      indicator.style.opacity = '0';
      indicator.style.transform = 'translateX(100px)';
    }, 2000);
  }

  // Function to update URL based on current step
  function updateURL(stepIndex) {
    console.log('🎯 updateURL called:', {stepIndex, totalSteps: steps.length, currentURL: window.location.href});

    let newURL = baseUrl;

    if (stepIndex === 0) {
      // First step - just base URL with trailing slash
      newURL = baseUrl + '/';
    } else if (stepIndex === steps.length - 1) {
      // Last step - this should not normally be reached because
      // the completion logic automatically redirects to /zavrsena-anketa
      // But if it is reached, don't change URL, let the automatic redirect handle it
      console.log('⚠️ Last step reached in updateURL - should be handled by automatic redirect');
      newURL = window.location.href; // Keep current URL
    } else {
      // Question steps - pitanja1, pitanja2, etc. with trailing slash
      newURL = baseUrl + '/pitanja' + stepIndex + '/';
    }

    console.log('🚀 Will navigate to:', newURL);

    // Use window.location.href to force page refresh with new URL
    if (window.location.href !== newURL) {
      console.log('🔄 Executing navigation...');
      window.location.href = newURL;
    } else {
      console.log('⚠️ URL already matches, no navigation needed');
    }
  }

  // Function to get step description for better UX
  function getStepDescription(stepIndex) {
    if (stepIndex === 0) {
      return 'Osnovni podaci';
    } else if (stepIndex === steps.length - 1) {
      return 'Izveštaj i preporuke';
    } else {
      return 'Pitanja ' + stepIndex;
    }
  }

  // Optimized auto-save system
  let autoSaveTimeout = null;
  let lastSaveTime = 0;
  const AUTO_SAVE_DELAY = 500; // Faster response - 500ms
  const MIN_SAVE_INTERVAL = 2000; // Minimum 2 seconds between saves

  function triggerAutoSave(immediate = false) {
    const now = Date.now();

    if (autoSaveTimeout) {
      clearTimeout(autoSaveTimeout);
    }

    // If immediate save requested or enough time has passed
    if (immediate || (now - lastSaveTime) >= MIN_SAVE_INTERVAL) {
      autoSaveToDatabase();
      lastSaveTime = now;
    } else {
      // Otherwise, schedule save with debouncing
      autoSaveTimeout = setTimeout(() => {
        autoSaveToDatabase();
        lastSaveTime = Date.now();
      }, AUTO_SAVE_DELAY);
    }
  }

  function setupAutoSaveListeners() {
    console.log('🔧 Setting up optimized auto-save listeners...');

    const inputFields = [
      'wvp-first-name', 'wvp-last-name', 'wvp-email', 'wvp-phone',
      'wvp-year', 'wvp-location', 'wvp-country'
    ];

    inputFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        // Immediate save on blur for important fields
        field.addEventListener('input', () => triggerAutoSave(false));
        field.addEventListener('blur', () => triggerAutoSave(true));
        console.log('✅ Auto-save listener added for:', fieldId);
      }
    });

    // Enhanced quiz answer listeners
    quiz.addEventListener('change', function(e) {
      if (e.target.type === 'radio') {
        console.log('🔘 Radio button changed:', e.target.name, '=', e.target.value);

        // Immediate save for quiz answers - they're critical
        triggerAutoSave(true);

        // Show/hide intensity groups immediately
        const questionGroup = e.target.closest('.wvp-health-question-group');
        if (questionGroup) {
          const intensityGroup = questionGroup.querySelector('.wvp-health-intensity-group');
          if (intensityGroup) {
            if (e.target.value.toLowerCase() === 'da') {
              intensityGroup.style.display = 'block';
            } else {
              intensityGroup.style.display = 'none';
              // Clear intensity selection when hiding
              const intensityInputs = intensityGroup.querySelectorAll('input[type="radio"]');
              intensityInputs.forEach(input => input.checked = false);
            }
          }
        }
      }
    });

    // Additional listener for intensity changes
    quiz.addEventListener('change', function(e) {
      if (e.target.type === 'radio' && e.target.closest('.wvp-health-intensity-group')) {
        console.log('🎚️ Intensity changed:', e.target.name, '=', e.target.value);
        triggerAutoSave(true); // Immediate save for intensity too
      }
    });

    console.log('✅ All auto-save listeners configured successfully');
  }

  function loadState(){
    // Initialize session first
    initializeSession();

    // Setup auto-save listeners
    setupAutoSaveListeners();

    try{
      const saved=JSON.parse(localStorage.getItem(STORAGE_KEY)||'null');
      if(!saved) return;
      if(saved.first_name) document.getElementById('wvp-first-name').value=saved.first_name;
      if(saved.last_name) document.getElementById('wvp-last-name').value=saved.last_name;
      if(saved.email) document.getElementById('wvp-email').value=saved.email;
      if(saved.phone) document.getElementById('wvp-phone').value=saved.phone;
      if(saved.year) document.getElementById('wvp-year').value=saved.year;
      if(saved.location) document.getElementById('wvp-location').value=saved.location;
      if(saved.country) document.getElementById('wvp-country').value=saved.country;
      if(saved.answers){
        Object.keys(saved.answers).forEach(q=>{
          const input=quiz.querySelector('.wvp-health-question-group[data-question="'+q+'"] input[data-index="'+saved.answers[q]+'"]');
          if(input) {
            input.checked=true;
            // Show intensity group if "Da" is selected
            if(input.value.toLowerCase() === 'da'){
              const questionGroup = input.closest('.wvp-health-question-group');
              const intensityGroup = questionGroup.querySelector('.wvp-health-intensity-group');
              if(intensityGroup) intensityGroup.style.display = 'block';
            }
          }
        });
      }
      if(saved.intensities){
        Object.keys(saved.intensities).forEach(q=>{
          const input=quiz.querySelector('.wvp-health-intensity-group[data-question="'+q+'"] input[data-intensity="'+saved.intensities[q]+'"]');
          if(input) input.checked=true;
        });
      }
      if(saved.resultId) resultId=saved.resultId;
      // Only use saved step if not overridden by URL
      // Check if initial_step is set (could be 0, 1, 2, etc.)
      if (wvpHealthData.initial_step === undefined || wvpHealthData.initial_step === null || wvpHealthData.initial_step === '') {
        currentStep=Math.min(saved.step||0,steps.length-1);
      }
      applyResults();
      showStep(currentStep);
    }catch(e){}
  }
  function showStep(index){
    console.log('📍 showStep called with index:', index, 'currentStep was:', currentStep);
    currentStep=index;
    console.log('📱 Total steps available:', steps.length, 'showing step:', index);

    steps.forEach((s,i)=>{
      s.style.display=i===index?'block':'none';
      console.log(`Step ${i}: ${s.style.display === 'block' ? 'VISIBLE' : 'hidden'}`);
    });

    // URL routing now handled by PHP redirects

    if(currentStep===steps.length-1){
      console.log('🏁 Reached completion step (final step)');
      // Ensure answers are saved when we reach completion step
      if (!saveAnswersPromise && !resultId) {
        console.log('🚀 Completion step reached but no AJAX called yet. Triggering save...');
        console.log('📡 AJAX URL:', wvpHealthData.ajaxurl);
        console.log('🔐 Nonce:', wvpHealthData.nonce);
        applyResults();
        const data=new FormData();
        data.append('action','bulletproof_save_answers');
        data.append('nonce',wvpHealthData.nonce);
        data.append('first_name',document.getElementById('wvp-first-name').value);
        data.append('last_name',document.getElementById('wvp-last-name').value);
        data.append('email',document.getElementById('wvp-email').value);
        data.append('phone',document.getElementById('wvp-phone').value);
        data.append('birth_year',document.getElementById('wvp-year').value);
        data.append('location',document.getElementById('wvp-location').value);
        data.append('country',document.getElementById('wvp-country').value);
        // BULLETPROOF: Use consistent format everywhere
        const currentAnswers = {};
        const currentIntensities = {};

        quiz.querySelectorAll('.wvp-health-question-group').forEach(g => {
          const questionIndex = g.dataset.question;
          const selected = g.querySelector('input[type="radio"]:checked');
          if (selected) {
            currentAnswers[questionIndex] = selected.value;

            const intensityGroup = g.querySelector('.wvp-health-intensity-group');
            if (intensityGroup && intensityGroup.style.display !== 'none') {
              const intensitySelected = intensityGroup.querySelector('input[type="radio"]:checked');
              if (intensitySelected) {
                currentIntensities[questionIndex] = intensitySelected.value;
              }
            }
          }
        });

        data.append('answers_data', JSON.stringify(currentAnswers));
        data.append('intensities_data', JSON.stringify(currentIntensities));
        data.append('session_id', sessionId); // Session ID for tracking
        console.log('About to send AJAX request to:', wvpHealthData.ajaxurl);
        console.log('FormData contents:', Array.from(data.entries()));
        saveAnswersPromise=fetch(wvpHealthData.ajaxurl,{method:'POST',body:data,credentials:'same-origin'})
          .then(r=>{
            console.log('AJAX response status:', r.status);
            return r.json();
          })
          .then(res=>{
            console.log('✅ FINAL AJAX response received:', res);
            if(res.success){
              resultId=res.data.result_id;
              // Save result ID to localStorage for the completed page
              localStorage.setItem('wvp_health_quiz_result_id', resultId);
              // Save public analysis ID if available
              if (res.data.public_analysis_id) {
                localStorage.setItem('wvp_health_quiz_public_id', res.data.public_analysis_id);
              }
              // Save session ID if available
              if (res.data.session_id) {
                sessionId = res.data.session_id;
                localStorage.setItem(SESSION_KEY, sessionId);
              }
              saveState();
            }else{
              alert(res.data&&res.data.message?res.data.message:'Greška pri snimanju.');
              if(res.data&&res.data.log) showDebug(res.data.log);
            }
          })
          .catch(()=>{alert('Greška pri snimanju.');showDebug('Network error');});
      }

      // Wait for AJAX to complete, then redirect to completed page
      function redirectToCompleted() {
        console.log('🔄 redirectToCompleted called - resultId:', resultId, 'saveAnswersPromise:', !!saveAnswersPromise);

        if (resultId && resultId !== null) {
          // Result ID is available, redirect now
          console.log('✅ Redirecting with Result ID:', resultId);
          const completedUrl = baseUrl.replace(/\/?$/,'')+'/zavrsena-anketa?result_id=' + resultId;
          console.log('🎯 Redirecting to:', completedUrl);
          window.location.href = completedUrl;
        } else if (saveAnswersPromise) {
          // Wait for the AJAX promise to complete
          console.log('Waiting for AJAX to complete...');
          saveAnswersPromise.then(function() {
            setTimeout(redirectToCompleted, 500); // Check again after 500ms
          }).catch(function() {
            setTimeout(redirectToCompleted, 500); // Try again even if error
          });
        } else {
          // No AJAX promise, check again in 1 second
          setTimeout(redirectToCompleted, 1000);
        }
      }
      setTimeout(redirectToCompleted, 2000); // Start checking after 2 seconds
    }else{
      history.replaceState({},'',baseUrl);
    }
    document.dispatchEvent(new CustomEvent('wvpHealthQuizStepChange',{detail:{stepCount:steps.length,currentStep:index}}));
    if(currentStep===steps.length-1){
      document.dispatchEvent(new Event('wvpHealthQuizComplete'));
    }
    saveState();
    steps[index].scrollIntoView({behavior:'smooth',block:'start'});
    window.scrollTo({top:0,behavior:'smooth'});
  }
  function clearErrors(scope){
    scope.querySelectorAll('.wvp-health-error').forEach(e=>{e.textContent='';e.style.display='none';});
  }
  function showError(input,msg){
    const err=input.parentElement.querySelector('.wvp-health-error');
    if(err){err.textContent=msg;err.style.display='block';}
  }
  function gatherIndexes(){
    const indexes=[];
    quiz.querySelectorAll('.wvp-health-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(sel) indexes.push(sel.dataset.index);
    });
    return indexes;
  }
  function gatherAnswers(){
    const ans=[];
    quiz.querySelectorAll('.wvp-health-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(sel) ans.push(sel.value);
    });
    return ans;
  }
  function gatherIntensities(){
    const intensities={};
    quiz.querySelectorAll('.wvp-health-question-group').forEach(g=>{
      const questionIndex = g.dataset.question;
      const intensityGroup = g.querySelector('.wvp-health-intensity-group');
      if(intensityGroup){
        const selectedIntensity = intensityGroup.querySelector('input:checked');
        if(selectedIntensity){
          intensities[questionIndex] = selectedIntensity.dataset.intensity;
        }
      }
    });
    return intensities;
  }
  quiz.querySelectorAll('input').forEach(inp=>{
    inp.addEventListener('input',()=>{
      const err=inp.parentElement.querySelector('.wvp-health-error');
      if(err){err.textContent='';err.style.display='none';}
      saveState();
    });
  });
  quiz.querySelectorAll('.wvp-health-question-group input').forEach(inp=>{
    inp.addEventListener('change',()=>{
      const err=inp.closest('.wvp-health-question-group').querySelector('.wvp-health-error');
      if(err){err.textContent='';err.style.display='none';}

      // Handle intensity levels display
      if(inp.classList.contains('wvp-health-question')){
        const questionGroup = inp.closest('.wvp-health-question-group');
        const intensityGroup = questionGroup.querySelector('.wvp-health-intensity-group');
        if(intensityGroup){
          if(inp.value.toLowerCase() === 'da' && inp.checked){
            intensityGroup.style.display = 'block';
          } else {
            intensityGroup.style.display = 'none';
            // Clear intensity selection if No is selected
            intensityGroup.querySelectorAll('input[type="radio"]').forEach(radio => {
              radio.checked = false;
            });
          }
        }
      }

      saveState();
    });
  });

  // Add event listeners for intensity radio buttons
  quiz.querySelectorAll('.wvp-health-intensity-radio').forEach(inp=>{
    inp.addEventListener('change',()=>{
      const err=inp.closest('.wvp-health-question-group').querySelector('.wvp-health-error');
      if(err){err.textContent='';err.style.display='none';}
      saveState();
    });
  });

  // Navigation: Hybrid approach - JavaScript for auto-save, but allow PHP forms for navigation
  quiz.querySelectorAll('.wvp-health-next').forEach(btn=>{
    btn.addEventListener('click',async function(e){
      console.log('Next button clicked');

      // If this is a form button, allow form submission after auto-save
      if(btn.type === 'submit') {
        console.log('Form submission detected, auto-saving first...');
        try {
          autoSaveToDatabase();
          // Allow form to submit normally
          return;
        } catch(err) {
          console.error('Auto-save failed:', err);
          // Still allow form submission
          return;
        }
      }

      // Original JavaScript navigation for non-form buttons
      e.preventDefault();
      const stepElem=this.closest('.wvp-health-step');
      const step=parseInt(stepElem.dataset.step);
      console.log('Current step from button:', step);
      clearErrors(stepElem);
      if(step===1){
        const firstNameInput=document.getElementById('wvp-first-name');
        const lastNameInput=document.getElementById('wvp-last-name');
        const emailInput=document.getElementById('wvp-email');
        const phoneInput=document.getElementById('wvp-phone');
        const yearInput=document.getElementById('wvp-year');
        const countryInput=document.getElementById('wvp-country');
        const firstName=firstNameInput.value.trim();
        const lastName=lastNameInput.value.trim();
        const email=emailInput.value.trim();
        const phone=phoneInput.value.trim();
        const year=yearInput.value.trim();
        const country=countryInput.value.trim();
        let valid=true;
        if(!firstName){showError(firstNameInput,'Unesite ime.');valid=false;}
        if(!lastName){showError(lastNameInput,'Unesite prezime.');valid=false;}
        if(!email){showError(emailInput,'Unesite email.');valid=false;}
        else if(!/^([^\s@]+)@([^\s@]+)\.[^\s@]+$/.test(email)){showError(emailInput,'Neispravan email.');valid=false;}
        if(!phone){showError(phoneInput,'Unesite telefon.');valid=false;}
        else if(!/^[0-9]+$/.test(phone)){showError(phoneInput,'Telefon mora da sadrzi samo brojeve');valid=false;}
        if(!year){showError(yearInput,'Unesite godinu rođenja.');valid=false;}
        if(!country){showError(countryInput,'Izaberite zemlju.');valid=false;}
        if(!valid) return;
      }else{
        let valid=true;
        stepElem.querySelectorAll('.wvp-health-question-group').forEach(g=>{
          const selectedAnswer = g.querySelector('input:checked');
          if(!selectedAnswer){
            const err=g.querySelector('.wvp-health-error');
            if(err){err.textContent='Odaberite odgovor.';err.style.display='block';}
            valid=false;
          } else {
            // Check if "Da" is selected and intensity group exists but no intensity is selected
            if(selectedAnswer.value.toLowerCase() === 'da'){
              const intensityGroup = g.querySelector('.wvp-health-intensity-group');
              if(intensityGroup && intensityGroup.style.display !== 'none'){
                const selectedIntensity = intensityGroup.querySelector('input:checked');
                if(!selectedIntensity){
                  const err=g.querySelector('.wvp-health-error');
                  if(err){err.textContent='Odaberite intenzitet.';err.style.display='block';}
                  valid=false;
                }
              }
            }
          }
        });
        if(!valid) return;
      }
      const next=step+1;
      console.log('🎯 Button navigation: current step:', step, 'next step:', next, 'total steps:', steps.length);
      if(next===steps.length){
        applyResults();
        const data=new FormData();
        data.append('action','bulletproof_save_answers');
        data.append('nonce',wvpHealthData.nonce);
        data.append('first_name',document.getElementById('wvp-first-name').value);
        data.append('last_name',document.getElementById('wvp-last-name').value);
        data.append('email',document.getElementById('wvp-email').value);
        data.append('phone',document.getElementById('wvp-phone').value);
        data.append('birth_year',document.getElementById('wvp-year').value);
        data.append('location',document.getElementById('wvp-location').value);
        data.append('country',document.getElementById('wvp-country').value);
        // BULLETPROOF: Use consistent format everywhere
        const currentAnswers = {};
        const currentIntensities = {};

        quiz.querySelectorAll('.wvp-health-question-group').forEach(g => {
          const questionIndex = g.dataset.question;
          const selected = g.querySelector('input[type="radio"]:checked');
          if (selected) {
            currentAnswers[questionIndex] = selected.value;

            const intensityGroup = g.querySelector('.wvp-health-intensity-group');
            if (intensityGroup && intensityGroup.style.display !== 'none') {
              const intensitySelected = intensityGroup.querySelector('input[type="radio"]:checked');
              if (intensitySelected) {
                currentIntensities[questionIndex] = intensitySelected.value;
              }
            }
          }
        });

        data.append('answers_data', JSON.stringify(currentAnswers));
        data.append('intensities_data', JSON.stringify(currentIntensities));
        data.append('session_id', sessionId); // Session ID for tracking
        console.log('About to send AJAX request to:', wvpHealthData.ajaxurl);
        console.log('FormData contents:', Array.from(data.entries()));
        saveAnswersPromise=fetch(wvpHealthData.ajaxurl,{method:'POST',body:data,credentials:'same-origin'})
          .then(r=>{
            console.log('AJAX response status:', r.status);
            return r.json();
          })
          .then(res=>{
            console.log('✅ FINAL AJAX response received:', res);
            if(res.success){
              resultId=res.data.result_id;
              // Save result ID to localStorage for the completed page
              localStorage.setItem('wvp_health_quiz_result_id', resultId);
              // Save public analysis ID if available
              if (res.data.public_analysis_id) {
                localStorage.setItem('wvp_health_quiz_public_id', res.data.public_analysis_id);
              }
              // Save session ID if available
              if (res.data.session_id) {
                sessionId = res.data.session_id;
                localStorage.setItem(SESSION_KEY, sessionId);
              }
              saveState();
            }else{
              alert(res.data&&res.data.message?res.data.message:'Greška pri snimanju.');
              if(res.data&&res.data.log) showDebug(res.data.log);
            }
          })
          .catch(()=>{alert('Greška pri snimanju.');showDebug('Network error');});
      }
      console.log('🎯 About to call showStep with:', next);
      showStep(next);
    });
  });

  quiz.querySelectorAll('.wvp-health-prev').forEach(btn=>{
    btn.addEventListener('click',function(){
      const stepElem=this.closest('.wvp-health-step');
      const step=parseInt(stepElem.dataset.step);
      const prev=step-1;
      showStep(prev);
    });
  });

  quiz.querySelectorAll('.wvp-health-select').forEach(btn=>{
    btn.addEventListener('click',async function(){
      try{
        const checkoutData={
          first_name:document.getElementById('wvp-first-name').value.trim(),
          last_name:document.getElementById('wvp-last-name').value.trim(),
          email:document.getElementById('wvp-email').value.trim(),
          phone:document.getElementById('wvp-phone').value.trim(),
          city:document.getElementById('wvp-location').value.trim(),
          country:document.getElementById('wvp-country').value.trim()
        };
        localStorage.setItem('wvp_health_checkout',JSON.stringify(checkoutData));
        localStorage.removeItem(STORAGE_KEY);
      }catch(e){}
      if(saveAnswersPromise) await saveAnswersPromise;
      const data=new FormData();
      data.append('action','wvp_set_product');
      data.append('nonce',wvpHealthData.nonce);
      data.append('result_id',resultId||0);
      data.append('product',this.dataset.product);

      try{
        fetch(wvpHealthData.ajaxurl,{
          method:'POST',
          body:data,
          credentials:'same-origin',
          keepalive:true
        });
        fetch(wvpHealthData.cart_url+'?add-to-cart='+this.dataset.product,{
          credentials:'same-origin',
          keepalive:true
        });
      }catch(e){
        console.error('Greška pri dodavanju proizvoda.',e);
      }

      window.location=wvpHealthData.checkout;
    });
  });

  // Professional SVG Body Map Interactivity
  function initBodyMap() {
    const organElements = document.querySelectorAll('.clickable-organ');
    const aiAnalysisPanel = document.querySelector('.ai-analysis-panel');

    if (!organElements.length) return;

    // Remove old region details if it exists
    const oldRegionDetails = document.getElementById('region-details');
    if (oldRegionDetails) {
      oldRegionDetails.remove();
    }

    // Create or get AI analysis panel
    let analysisPanel = aiAnalysisPanel;
    if (!analysisPanel) {
      analysisPanel = document.createElement('div');
      analysisPanel.className = 'ai-analysis-panel';
      analysisPanel.innerHTML = `
        <h3 class="analysis-title">AI Analiza Organa</h3>
        <div class="analysis-content">
          <div class="organ-info">
            <h4 class="organ-title">Kliknite na organ za analizu</h4>
            <div class="health-indicators">
              <div class="indicator-section">
                <h5>🔍 Simptomi</h5>
                <p class="symptoms-text">Izaberite organ za detaljnu analizu...</p>
              </div>
              <div class="indicator-section">
                <h5>⚠️ Mogući uzroci</h5>
                <p class="causes-text">Informacije će biti prikazane nakon izbora organa.</p>
              </div>
              <div class="indicator-section">
                <h5>💡 Prirodna rešenja</h5>
                <p class="solutions-text">Personalizovani saveti za poboljšanje zdravlja.</p>
              </div>
            </div>
          </div>
        </div>
      `;

      // Insert after body map container
      const bodyMapContainer = document.querySelector('.body-map-container');
      if (bodyMapContainer) {
        bodyMapContainer.parentNode.insertBefore(analysisPanel, bodyMapContainer.nextSibling);
      }
    }

    // Add organ click handlers
    organElements.forEach(organ => {
      organ.addEventListener('click', function() {
        const regionType = this.dataset.region;
        const title = this.dataset.title || 'Nepoznat organ';
        const symptoms = this.dataset.symptoms || 'Nema dostupnih podataka';
        const causes = this.dataset.causes || 'Nema dostupnih podataka';
        const solutions = this.dataset.solutions || 'Nema dostupnih podataka';

        // Update analysis panel
        analysisPanel.querySelector('.organ-title').textContent = title;
        analysisPanel.querySelector('.symptoms-text').textContent = symptoms;
        analysisPanel.querySelector('.causes-text').textContent = causes;
        analysisPanel.querySelector('.solutions-text').textContent = solutions;

        // Remove active class from all organs
        organElements.forEach(o => o.classList.remove('active'));

        // Add active class to clicked organ
        this.classList.add('active');

        // Show analysis panel with smooth animation
        analysisPanel.style.display = 'block';
        analysisPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });

      // Add hover effects
      organ.addEventListener('mouseenter', function() {
        if (!this.classList.contains('active')) {
          this.style.opacity = '0.9';
        }
      });

      organ.addEventListener('mouseleave', function() {
        if (!this.classList.contains('active')) {
          this.style.opacity = '';
        }
      });
    });
  }

  // Progress Bar Updates
  function updateProgressBar() {
    const progressFills = document.querySelectorAll('.progress-fill');
    const progressTexts = document.querySelectorAll('.progress-text');
    const totalSteps = steps.length - 1; // Exclude form step

    progressFills.forEach(fill => {
      const progress = Math.max(0, (currentStep - 1) / totalSteps * 100);
      fill.style.width = progress + '%';
    });

    progressTexts.forEach(text => {
      if (currentStep > 0) {
        text.textContent = `Korak ${currentStep}/${totalSteps}`;
      }
    });
  }

  // Update the existing showStep function to include progress bar
  const originalShowStep = showStep;
  showStep = function(step) {
    originalShowStep(step);
    updateProgressBar();

    // Initialize body map when reaching results step
    if (steps[currentStep] && steps[currentStep].classList.contains('wvp-results-step')) {
      setTimeout(initBodyMap, 100);
    }
  };

  loadState();

  // BULLETPROOF ANSWER TRACKING SYSTEM
  // This system will directly track every radio button change and save answers immediately
  setTimeout(() => {
    console.log('🔄 Setting up bulletproof answer tracking system...');

    // Find all radio buttons in question groups
    const questionRadios = document.querySelectorAll('.wvp-health-question-group input[type="radio"]');
    const intensityRadios = document.querySelectorAll('.wvp-health-intensity-group input[type="radio"]');

    console.log('🎯 Found question radios:', questionRadios.length);
    console.log('🎯 Found intensity radios:', intensityRadios.length);

    // Function to immediately save answers when any radio button changes
    function saveAnswersImmediately() {
      const answers = {};
      const intensities = {};

      // Collect all checked question radios
      document.querySelectorAll('.wvp-health-question-group input[type="radio"]:checked').forEach(radio => {
        const questionGroup = radio.closest('.wvp-health-question-group');
        if (questionGroup && questionGroup.dataset.question !== undefined) {
          const questionIndex = questionGroup.dataset.question;
          answers[questionIndex] = radio.value;
          console.log('📝 Question ' + questionIndex + ' = ' + radio.value);
        }
      });

      // Collect all checked intensity radios
      document.querySelectorAll('.wvp-health-intensity-group input[type="radio"]:checked').forEach(radio => {
        const intensityGroup = radio.closest('.wvp-health-intensity-group');
        const questionGroup = intensityGroup ? intensityGroup.closest('.wvp-health-question-group') : null;
        if (questionGroup && questionGroup.dataset.question !== undefined) {
          const questionIndex = questionGroup.dataset.question;
          intensities[questionIndex] = radio.value;
          console.log('📊 Intensity ' + questionIndex + ' = ' + radio.value);
        }
      });

      console.log('💾 BULLETPROOF: Saving answers:', answers);
      console.log('💾 BULLETPROOF: Saving intensities:', intensities);

      // Send immediately to server using bulletproof save endpoint
      const data = new FormData();
      data.append('action', 'bulletproof_save_answers');
      data.append('nonce', wvpHealthData.nonce);
      data.append('session_id', sessionId || '');
      data.append('result_id', resultId || '0');

      // Add form data if available
      const firstNameEl = document.getElementById('wvp-first-name');
      const lastNameEl = document.getElementById('wvp-last-name');
      const emailEl = document.getElementById('wvp-email');
      const phoneEl = document.getElementById('wvp-phone');
      const yearEl = document.getElementById('wvp-year');
      const locationEl = document.getElementById('wvp-location');
      const countryEl = document.getElementById('wvp-country');

      if (firstNameEl) data.append('first_name', firstNameEl.value || '');
      if (lastNameEl) data.append('last_name', lastNameEl.value || '');
      if (emailEl) data.append('email', emailEl.value || '');
      if (phoneEl) data.append('phone', phoneEl.value || '');
      if (yearEl) data.append('birth_year', yearEl.value || '1990');
      if (locationEl) data.append('location', locationEl.value || '');
      if (countryEl) data.append('country', countryEl.value || '');

      // Send answers as JSON strings
      data.append('answers_data', JSON.stringify(answers));
      data.append('intensities_data', JSON.stringify(intensities));

      fetch(wvpHealthData.ajaxurl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          console.log('✅ BULLETPROOF: Answers saved successfully!', result.data);
          if (result.data.result_id && !resultId) {
            resultId = result.data.result_id;
            localStorage.setItem('wvp_health_quiz_result_id', resultId);
          }
          if (result.data.session_id && !sessionId) {
            sessionId = result.data.session_id;
            localStorage.setItem(SESSION_KEY, sessionId);
          }
        } else {
          console.error('❌ BULLETPROOF: Save failed:', result.data);
        }
      })
      .catch(error => {
        console.error('❌ BULLETPROOF: Network error:', error);
      });
    }

    // Add change listeners to all question radios
    questionRadios.forEach((radio) => {
      radio.addEventListener('change', function() {
        console.log('🔘 Question radio changed:', this.name, '=', this.value);
        saveAnswersImmediately();
      });
    });

    // Add change listeners to all intensity radios
    intensityRadios.forEach((radio) => {
      radio.addEventListener('change', function() {
        console.log('🔘 Intensity radio changed:', this.name, '=', this.value);
        saveAnswersImmediately();
      });
    });

    console.log('✅ Bulletproof answer tracking system activated!');
  }, 1000);
});