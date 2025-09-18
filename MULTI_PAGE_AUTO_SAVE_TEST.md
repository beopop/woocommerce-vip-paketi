# Multi-Page Auto-Save Bug Test Case

## Problem Description
Auto-save functionality works on the main page but stops working when users navigate to question pages with different URLs.

## Test Environment
- **Main URL**: http://testni-sajt.local/analiza-zdravstvenog-stanja/
- **Question Pages**:
  - http://testni-sajt.local/analiza-zdravstvenog-stanja/pitanja1/
  - http://testni-sajt.local/analiza-zdravstvenog-stanja/pitanja2/
  - etc.

## Bug Reproduction Steps

### Step 1: Initial Page Load Test
1. Open: http://testni-sajt.local/analiza-zdravstvenog-stanja/
2. Open Developer Tools (F12) → Console tab
3. Fill in basic form data (name, email)
4. **Expected**: Should see auto-save logs and "✅ Sačuvano" indicator
5. **Expected**: Data should be saved to database

### Step 2: Question Page Navigation Test
1. Click "Zapocni AI Analizu" to navigate to questions
2. URL changes to: http://testni-sajt.local/analiza-zdravstvenog-stanja/pitanja1/
3. Select any radio button answer
4. **Current Bug**: No auto-save logs in console
5. **Current Bug**: No "✅ Sačuvano" indicator appears
6. **Current Bug**: Question answers are NOT saved to database

### Step 3: Direct Question Page Access Test
1. Directly visit: http://testni-sajt.local/analiza-zdravstvenog-stanja/pitanja1/
2. Fill form data and select answers
3. **Current Bug**: Auto-save system not initialized
4. **Current Bug**: No data saved to database

### Step 4: Admin Panel Verification
1. Go to: http://testni-sajt.local/wp-admin/admin.php?page=wvp-health-quiz-results
2. Open any recent result
3. **Current Bug**: Shows "Nema odgovora" for questions
4. **Expected**: Should show actual user answers

## Root Cause Analysis

### 1. JavaScript Initialization Issue
- Auto-save listeners are set up only once on initial page load
- When navigating between quiz steps, page content changes but JavaScript doesn't re-initialize
- Different URLs may load different templates without proper JS initialization

### 2. URL Routing Issues
- Quiz uses multiple URL patterns (/pitanja1/, /pitanja2/)
- Auto-save system may not be consistent across all URL patterns
- Session tracking might break across page transitions

### 3. Database Saving Problems
- Question answers might use different data format than expected
- Admin panel might not read the correct database fields
- Session ID might not persist across pages

## Expected Behavior
1. Auto-save should work consistently on ALL quiz pages
2. Question answers should save immediately when selected
3. Form data should persist across page navigation
4. Admin panel should display all saved data correctly
5. "✅ Sačuvano" indicator should appear on every page

## Files to Investigate
- `/assets/js/health-quiz.js` - Main JavaScript initialization
- `/includes/health-quiz/template.php` - Quiz page templates
- `/includes/health-quiz/shortcodes.php` - URL routing logic
- `/includes/health-quiz/data-handler.php` - Database save functions
- `/includes/admin/partials/wvp-admin-health-quiz-detailed-report.php` - Admin display

## Test Data to Check
- Database table: `wp_wvp_health_quiz_results`
- Fields to verify: `answers`, `intensity_data`, `session_id`
- Expected format: JSON strings like `{"0":"Da","1":"Ne"}`

## Success Criteria
✅ Auto-save works on main page
❌ Auto-save works on /pitanja1/ page
❌ Auto-save works on /pitanja2/ page
❌ Direct access to question pages works
❌ Question answers appear in admin panel
❌ Visual save indicators work on all pages