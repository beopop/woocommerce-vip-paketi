document.addEventListener('DOMContentLoaded',function(){
  try{
    const stored=localStorage.getItem('wvp_health_checkout');
    if(!stored) return;
    const data=JSON.parse(stored);
    const map={
      first_name:'#billing_first_name',
      last_name:'#billing_last_name',
      email:'#billing_email',
      phone:'#billing_phone',
      city:'#billing_city',
      country:'#billing_country'
    };
    Object.keys(map).forEach(key=>{
      if(data[key]){
        const el=document.querySelector(map[key]);
        if(el&& !el.value){
          el.value=data[key];
        }
      }
    });
    localStorage.removeItem('wvp_health_checkout');
  }catch(e){
    console.error('WVP Health Quiz checkout fill error',e);
  }
});