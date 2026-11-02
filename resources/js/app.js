
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import PartnerWorkspace from './components/PartnerWorkspace.jsx';
import AdminDashboard from './components/AdminDashboard.jsx';
import FieldAgentDashboard from './field-agent/FieldAgentDashboard.jsx';
import PartnerDashboard from './partner/PartnerDashboard.jsx';
import AdminLearners from './components/AdminDashboard/Leaner/AdminLearners.jsx';
import LearnerSync from './components/AdminDashboard/LearnerSync.jsx';
// NEW: Placement Partner Dashboard
import PlacementPartnerDashboard from './components/PlacementPartnerDashboard/PlacementPartnerDashboard.jsx';

const components = {
    'partner-workspace': PartnerWorkspace,
    'admin-dashboard': AdminDashboard,
    'field-agent-dashboard': FieldAgentDashboard,
    'partner-dashboard': PartnerDashboard,
    'admin-learners': AdminLearners,
    'admin-learner-sync': LearnerSync,

    // NEW MODULE
    'placement-partner-dashboard': PlacementPartnerDashboard,
};

document.querySelectorAll('[data-react-app]').forEach((element) => {
    const Component = components[element.dataset.reactApp];

    if (!Component) {
        return;
    }

    // Field Agent Object
    const agent = element.dataset.agent
        ? JSON.parse(element.dataset.agent)
        : {};

    // Partner Object
    const partner = element.dataset.partner
        ? JSON.parse(element.dataset.partner)
        : {};

    const centers = element.dataset.centers
        ? JSON.parse(element.dataset.centers)
        : [];

    // NEW: Placement Partner Object
    const placementPartner = element.dataset.placementPartner
        ? JSON.parse(element.dataset.placementPartner)
        : {};

    createRoot(element).render(
        createElement(Component, {
            partnerName: element.dataset.partnerName,
            adminName: element.dataset.adminName,

            agent,
            partner,
            centers,

            // Placement Partner
            placementPartner,
            placementPartnerName: element.dataset.placementPartnerName,
            logoutUrl: element.dataset.logoutUrl,

            canManageAdmins:
                element.dataset.canManageAdmins === 'true',

            canViewPartners:
                element.dataset.canViewPartners === 'true',

            canViewPartnerCenters:
                element.dataset.canViewPartnerCenters === 'true',

            canManagePlacementUsers:
                element.dataset.canManagePlacementUsers === 'true',

            canViewFieldAgents:
                element.dataset.canViewFieldAgents === 'true',

            canViewEventTypes:
                element.dataset.canViewEventTypes === 'true',

            canViewEventCategories:
                element.dataset.canViewEventCategories === 'true',

            canViewEventMasters:
                element.dataset.canViewEventMasters === 'true',

            canViewDownloads:
                element.dataset.canViewDownloads === 'true',

            canViewErrorLogs:
                element.dataset.canViewErrorLogs === 'true',

            fieldAgentName: element.dataset.fieldAgentName,
            fieldAgentStatus: element.dataset.fieldAgentStatus,


            // Admin Learners API endpoints
            summaryUrl: element.dataset.summaryUrl,
            analyticsUrl: element.dataset.analyticsUrl,
            listingUrl: element.dataset.listingUrl,
            filterOptionsUrl: element.dataset.filterOptionsUrl,

            // Admin Learners Laravel routes
            createUrl: element.dataset.createUrl,
            exportUrl: element.dataset.exportUrl,
            dashboardUrl: element.dataset.dashboardUrl,

            // CSRF token
            csrfToken: element.dataset.csrfToken,

            // Admin Learners permissions
            canView: element.dataset.canView === 'true',
            canCreate: element.dataset.canCreate === 'true',
            canUpdate: element.dataset.canUpdate === 'true',
            canDelete: element.dataset.canDelete === 'true',
            canExport: element.dataset.canExport === 'true',
        }),
    );
});
