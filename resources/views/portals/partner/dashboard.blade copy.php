@extends('layouts.app')

@section('content')
@php
    // Pass these scoped metrics from your controller. No illustrative numbers are displayed as real data.
    $centers = $partner->centers ?? collect();
    $totalLearners = $totalLearners ?? null;
    $totalAgents = $totalAgents ?? null;
    $totalTransactions = $totalTransactions ?? null;
    $growth = $growth ?? ['labels'=>[], 'learners'=>[], 'agents'=>[], 'transactions'=>[]];
    $transactionTypes = $transactionTypes ?? [];
    $recentActivities = $recentActivities ?? [];
    $initials = collect(preg_split('/\s+/', trim($partner->name ?? 'Partner')))->filter()->take(2)->map(fn($w)=>mb_strtoupper(mb_substr($w,0,1)))->implode('');
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
.pd,.pd *{box-sizing:border-box}.pd{--navy:#062756;--blue:#0868dc;--muted:#7185a5;display:flex;min-height:100vh;background:#f6f9fe;color:#092958;font-family:Inter,system-ui,sans-serif}.pd a{text-decoration:none}.pd button{font:inherit;cursor:pointer}.pd-side{width:274px;flex:0 0 274px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;padding:25px 14px;background:linear-gradient(155deg,#041e44,#082e65 70%,#063a7d);color:white;z-index:20}.pd-brand{display:flex;align-items:center;gap:10px;padding:4px 12px 28px;color:white;font-size:21px;font-weight:800;letter-spacing:-.8px;white-space:nowrap}.pd-brand img{width:42px;height:42px;object-fit:contain}.pd-brand strong{color:#2290ff}.pd-nav{display:grid;gap:7px}.pd-nav a,.pd-logout{display:flex;align-items:center;gap:16px;width:100%;padding:15px 18px;color:#d9e5fa;border:0;border-radius:9px;background:transparent;font-size:14px;text-align:left}.pd-nav a i,.pd-logout i{width:19px;font-size:18px;text-align:center}.pd-nav a:hover,.pd-nav a.active,.pd-logout:hover{background:#0968dc;color:#fff}.pd-divider{border:0;border-top:1px solid #ffffff36;margin:21px 9px}.pd-bottom{margin-top:auto;border-top:1px solid #ffffff30;padding-top:12px}.pd-main{min-width:0;flex:1}.pd-top{height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 30px;border-bottom:1px solid #e9eff8}.pd-top-left,.pd-user{display:flex;align-items:center;gap:18px}.pd-menu{border:0;background:none;color:#0a3977;font-size:21px}.pd-pill{background:#eaf3ff;border-radius:30px;padding:9px 15px;font-size:11px;font-weight:800;color:#0766d7;letter-spacing:.4px}.pd-user{gap:12px}.pd-avatar{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:#0968dc;color:#fff;font-weight:800}.pd-user strong{font-size:12px;display:block}.pd-user small{color:var(--muted);font-size:11px}.pd-content{padding:0 22px 35px;max-width:1800px;margin:auto}.pd-welcome{margin:0 -22px 20px;padding:34px 32px;min-height:140px;background:linear-gradient(100deg,#f9fcff 45%,#e4f0ff);display:flex;justify-content:space-between;align-items:center;gap:20px}.pd-welcome h1{font-size:clamp(23px,2.3vw,32px);letter-spacing:-1px;margin:0 0 12px}.pd-welcome h1 span{color:#0967d9}.pd-welcome p{margin:0;color:#617b9e;font-size:14px}.pd-quote{color:#19417a;font-size:19px;font-style:italic;max-width:245px}.pd-summary-error{margin-bottom:12px;padding:12px;border:1px solid #f5c2c2;background:#fff3f3;color:#9d3030;border-radius:9px;font-size:13px}.pd-summary-retry{margin-left:12px;border:0;background:#0868dc;color:white;border-radius:6px;padding:6px 12px}.pd-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-bottom:18px}.pd-card,.pd-panel{background:#fff;border:1px solid #dbe8fa;border-radius:11px;box-shadow:0 3px 14px #09295805}.pd-card{padding:22px;display:flex;align-items:center;gap:19px;min-height:122px}.pd-card.blue{background:#f0f6ff;border-color:#cbdfff}.pd-card.green{background:#eefbf7;border-color:#c9ede3}.pd-card.yellow{background:#fffaf0;border-color:#f8e4b8}.pd-icon{width:54px;height:54px;flex:0 0 54px;display:grid;place-items:center;border-radius:50%;background:#4c7ff4;color:white;font-size:22px}.green .pd-icon{background:#0ab58b}.yellow .pd-icon{background:#ffb51b}.pd-card small{font-size:12px;font-weight:700}.pd-card strong{display:block;margin-top:6px;font-size:28px;letter-spacing:-1px}.pd-card .pd-arrow{margin-left:auto;color:#0968dc}.pd-grid{display:grid;grid-template-columns:1.35fr .95fr .95fr;gap:16px;align-items:stretch}.pd-panel{padding:20px;min-width:0}.pd-panel h2{font-size:15px;margin:0 0 7px}.pd-panel p{font-size:12px;color:var(--muted);margin:0}.pd-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:20px}.pd-chip{background:#eef5ff;color:#0968dc;border-radius:20px;padding:7px 11px;font-size:11px;font-weight:700}.pd-chart{position:relative;height:235px}.pd-chart canvas{width:100%!important;height:100%!important}.pd-empty{color:#8495ad;font-size:13px;text-align:center;padding:70px 10px}.pd-activity{display:grid;gap:0}.pd-activity-item{display:flex;gap:11px;padding:13px 0;border-bottom:1px solid #edf1f8}.pd-activity-item:last-child{border:0}.pd-activity-item i{background:#eaf3ff;color:#0871e7;border-radius:50%;width:33px;height:33px;flex:0 0 33px;display:grid;place-items:center}.pd-activity-item strong{display:block;font-size:12px}.pd-activity-item small{display:block;color:var(--muted);font-size:11px;margin-top:5px}.pd-centers{grid-column:span 2}.pd-table-wrap{overflow-x:auto}.pd-table{width:100%;border-collapse:collapse;text-align:left;font-size:12px}.pd-table th{background:#f3f7fc;color:#60789a;font-size:11px}.pd-table th,.pd-table td{padding:14px 12px;border-bottom:1px solid #edf2f8}.pd-table td strong{font-size:12px}.pd-status{border-radius:20px;padding:5px 9px;background:#e8f8f0;color:#0b9d70;font-weight:700;font-size:10px}.pd-status:not(.active){background:#fff0e8;color:#bb692e}.pd-side-note{background:linear-gradient(135deg,#e9f4ff,#fff);display:flex;flex-direction:column;justify-content:center}.pd-side-note i{font-size:29px;color:#0868dc;margin-bottom:16px}.pd-side-note h2{font-size:19px}.pd-side-note p{line-height:1.8}.pd-side-note a{display:inline-block;align-self:flex-start;background:#0868dc;color:#fff;padding:12px 18px;border-radius:8px;font-size:12px;font-weight:700;margin-top:20px}.pd-react-root{margin-top:18px}.pd-backdrop{display:none}@media(max-width:1250px){.pd-grid{grid-template-columns:1fr 1fr}.pd-activities{grid-column:span 2}.pd-centers{grid-column:span 2}}@media(max-width:850px){.pd-side{position:fixed;left:0;transform:translateX(-100%);transition:transform .2s}.pd.sidebar-open .pd-side{transform:none}.pd.sidebar-open .pd-backdrop{display:block;position:fixed;inset:0;background:#071a3a88;z-index:19}.pd-cards{grid-template-columns:1fr}.pd-grid{grid-template-columns:1fr}.pd-activities,.pd-centers{grid-column:auto}.pd-quote{display:none}.pd-top{padding:0 18px}.pd-content{padding:0 14px 24px}.pd-welcome{margin:0 -14px 18px;padding:27px 18px}.pd-user-info{display:none}}@media(prefers-reduced-motion:reduce){.pd-side{transition:none}}
</style>
<div class="pd" id="partner-dashboard">
 <div class="pd-backdrop" id="pd-backdrop"></div>
 <aside class="pd-side" id="pd-sidebar" aria-label="Partner navigation">
  <a class="pd-brand" href="{{ route('partner.dashboard') }}"><img src="{{ asset('icon.png') }}" alt="YouthHub logo"><span>YouthHub <strong>Partner</strong></span></a>
  <nav class="pd-nav">
   <a class="active" href="{{ route('partner.dashboard') }}" data-page="dashboard"><i class="fa-solid fa-house"></i>Dashboard</a>
   <a href="#learners" data-page="learners"><i class="fa-solid fa-graduation-cap"></i>Learners</a>
   <a href="#event-transactions" data-page="event-transactions"><i class="fa-regular fa-calendar-days"></i>Event Transaction</a>
   <a href="#field-agents" data-page="field-agents"><i class="fa-solid fa-users"></i>Field Agents</a>
   <a href="#report" data-page="report"><i class="fa-solid fa-chart-column"></i>Report</a>
  </nav>
  <hr class="pd-divider">
  <nav class="pd-nav">
   <a href="#profile" data-page="profile"><i class="fa-regular fa-user"></i>Profile</a>
   <a href="#help-support" data-page="help-support"><i class="fa-regular fa-circle-question"></i>Help &amp; Support</a>
  </nav>
  <form class="pd-bottom" method="POST" action="{{ route('partner.logout') }}">@csrf<button type="submit" class="pd-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign Out</button></form>
 </aside>
 <div class="pd-main">
  <header class="pd-top"><div class="pd-top-left"><button type="button" class="pd-menu" id="pd-menu" aria-label="Toggle sidebar" aria-controls="pd-sidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i></button><span class="pd-pill">PARTNER WORKSPACE</span></div><div class="pd-user"><span class="pd-avatar">{{ $initials ?: 'P' }}</span><div class="pd-user-info"><strong>{{ $partner->name }}</strong><small>Partner · {{ $partner->partner_code }}</small></div></div></header>
  <div class="pd-content">
   <section id="pd-overview">
    <div class="pd-welcome"><div><h1>Welcome back, <span>{{ $partner->name }}</span></h1><p>Here's what's happening with your learners, field agents and event transactions.</p></div><div class="pd-quote">“Together we build brighter futures”</div></div>
    {{-- React owns only the summary cards in Step 6; the premium shell remains Blade. --}}
    <div data-react-app="partner-summary" data-summary-url="{{ route('partner.api.dashboard.summary') }}"></div>
    <div class="pd-grid">
     <section class="pd-panel"><div class="pd-panel-head"><h2><i class="fa-solid fa-arrow-trend-up" style="color:#0871e7"></i> Growth Overview</h2><span class="pd-chip">Last 6 Months</span></div><div class="pd-chart">@if(count($growth['labels'] ?? []))<canvas id="pd-growth"></canvas>@else<div class="pd-empty">Growth data will appear here when connected.</div>@endif</div></section>
     <section class="pd-panel"><div class="pd-panel-head"><h2>Event Transactions by Type</h2></div><div class="pd-chart">@if(count($transactionTypes))<canvas id="pd-types"></canvas>@else<div class="pd-empty">Transaction breakdown is not available yet.</div>@endif</div></section>
     <section class="pd-panel pd-activities"><div class="pd-panel-head"><h2><i class="fa-regular fa-clock" style="color:#0871e7"></i> Recent Activities</h2></div><div class="pd-activity">@forelse($recentActivities as $activity)<div class="pd-activity-item"><i class="fa-solid fa-bolt"></i><div><strong>{{ data_get($activity,'title','Activity') }}</strong><small>{{ data_get($activity,'description','') }}</small></div></div>@empty<div class="pd-empty">No recent activity yet.</div>@endforelse</div></section>
     <section class="pd-panel pd-centers"><div class="pd-panel-head"><div><h2><i class="fa-solid fa-building" style="color:#0871e7"></i> Your Centers</h2><p>Centers linked to this partner account.</p></div><span class="pd-chip">{{ $centers->count() }} total</span></div><div class="pd-table-wrap"><table class="pd-table"><thead><tr><th>Center Name</th><th>Partner Code</th><th>Status</th></tr></thead><tbody>@forelse($centers as $center)<tr><td><strong>{{ $center->name }}</strong></td><td>{{ $center->partner_code }}</td><td><span class="pd-status {{ strtolower($center->status) === 'active' ? 'active' : '' }}">{{ ucfirst($center->status) }}</span></td></tr>@empty<tr><td colspan="3" style="text-align:center;color:#7185a5">No centers linked to this partner yet.</td></tr>@endforelse</tbody></table></div></section>
     <section class="pd-panel pd-side-note"><i class="fa-solid fa-bullseye"></i><h2>Empowering Young Talent Together</h2><p>Your partnership helps create opportunities, build skills and shape brighter futures.</p><a href="#report" data-page="report">View Reports &nbsp; →</a></section>
    </div>
   </section>
   {{-- React can mount here later. Until then, navigation shows an honest placeholder. --}}
   <section id="pd-react-root" class="pd-react-root" hidden aria-live="polite"></section>
  </div>
 </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const shell=document.getElementById('partner-dashboard'),menu=document.getElementById('pd-menu'),backdrop=document.getElementById('pd-backdrop');
 function closeMenu(){shell.classList.remove('sidebar-open');menu.setAttribute('aria-expanded','false')}
 menu.addEventListener('click',()=>{const open=shell.classList.toggle('sidebar-open');menu.setAttribute('aria-expanded',String(open))});backdrop.addEventListener('click',closeMenu);
 const overview=document.getElementById('pd-overview'),root=document.getElementById('pd-react-root');
 const labels={'learners':'Learners','event-transactions':'Event Transaction','field-agents':'Field Agents','report':'Report','profile':'Profile','help-support':'Help & Support'};
 function navigate(){const key=decodeURIComponent(location.hash.slice(1));const isInner=Object.prototype.hasOwnProperty.call(labels,key);overview.hidden=isInner;root.hidden=!isInner;if(isInner&&!root.dataset.reactMounted){root.innerHTML='<div class="pd-panel"><h2>'+labels[key]+'</h2><p>This page will be connected to the React application in the next phase.</p></div>'}document.querySelectorAll('[data-page]').forEach(a=>a.classList.toggle('active',a.dataset.page===(isInner?key:'dashboard')));closeMenu()}
 window.addEventListener('hashchange',navigate);navigate();
 const growth=@json($growth),types=@json($transactionTypes);
 if(typeof Chart!=='undefined'){
  const gc=document.getElementById('pd-growth');if(gc&&growth.labels?.length)new Chart(gc,{type:'line',data:{labels:growth.labels,datasets:[{label:'Learners',data:growth.learners||[],borderColor:'#0877ed',backgroundColor:'#0877ed15',fill:true,tension:.4},{label:'Field Agents',data:growth.agents||[],borderColor:'#0ab58b',tension:.4},{label:'Event Transactions',data:growth.transactions||[],borderColor:'#ffb51b',tension:.4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',align:'start'}},scales:{y:{beginAtZero:true}}}});
  const tc=document.getElementById('pd-types');if(tc&&types.length)new Chart(tc,{type:'doughnut',data:{labels:types.map(x=>x.label),datasets:[{data:types.map(x=>x.value),backgroundColor:['#0877ed','#0ab58b','#ffb51b','#9152f6','#e87979']}]},options:{responsive:true,maintainAspectRatio:false,cutout:'67%',plugins:{legend:{position:'bottom'}}}});
 }
});
</script>
@endsection
