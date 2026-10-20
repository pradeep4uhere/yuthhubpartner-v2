import { lazy, Suspense, useState } from 'react';
import Sidebar from './components/Sidebar.jsx';
import Header from './components/Header.jsx';
import './PartnerDashboard.css';
const Overview = lazy(() => import('./pages/Overview.jsx'));
const Learners = lazy(() => import('./pages/Learners.jsx'));
const EventTransactions = lazy(() => import('./pages/EventTransactions.jsx'));
const FieldAgents = lazy(() => import('./pages/FieldAgents.jsx'));
const Reports = lazy(() => import('./pages/Reports.jsx'));
const Profile = lazy(() => import('./pages/Profile.jsx'));
const HelpSupport = lazy(() => import('./pages/HelpSupport.jsx'));
const pages = {dashboard:['Dashboard',Overview],learners:['Learners',Learners],transactions:['Event Transaction',EventTransactions],agents:['Field Agents',FieldAgents],reports:['Report',Reports],profile:['Profile',Profile],support:['Help & Support',HelpSupport]};
export default function PartnerDashboard({partnerName='Partner'}) {
 const [activePage,setActivePage]=useState('dashboard'); const [mobileOpen,setMobileOpen]=useState(false);
 const [title,ActivePage]=pages[activePage]||pages.dashboard;
 const navigate=page=>{setActivePage(page);setMobileOpen(false)};
 return <div className="yp-shell"><Sidebar activePage={activePage} onNavigate={navigate} mobileOpen={mobileOpen} onClose={()=>setMobileOpen(false)}/><main className="yp-main"><Header title={title} partnerName={partnerName} onMenu={()=>setMobileOpen(true)}/><Suspense fallback={<div className="yp-content"><div className="yp-panel">Loading...</div></div>}><ActivePage partnerName={partnerName} onNavigate={navigate}/></Suspense></main></div>;
}
