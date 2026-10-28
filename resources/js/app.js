
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import PartnerWorkspace from './components/PartnerWorkspace.jsx';
import AdminDashboard from './components/AdminDashboard.jsx';
import FieldAgentDashboard from './field-agent/FieldAgentDashboard.jsx';
import PartnerDashboard from './partner/PartnerDashboard.jsx';

const components = {
    'partner-workspace': PartnerWorkspace,
    'admin-dashboard': AdminDashboard,
    'field-agent-dashboard': FieldAgentDashboard,
    'partner-dashboard': PartnerDashboard,
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

    const partner = element.dataset.partner
        ? JSON.parse(element.dataset.partner)
        : {};

    const centers = element.dataset.centers
        ? JSON.parse(element.dataset.centers)
        : [];

    createRoot(element).render(
        createElement(Component, {
            partnerName: element.dataset.partnerName,
            adminName: element.dataset.adminName,

            agent,
            partner,
            centers,

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
        }),
    );
});
