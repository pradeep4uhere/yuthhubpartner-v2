
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

import PartnerWorkspace from './components/PartnerWorkspace.jsx';
import AdminDashboard from './components/AdminDashboard.jsx';
import FieldAgentDashboard from './field-agent/FieldAgentDashboard.jsx';

const components = {
    'partner-workspace': PartnerWorkspace,
    'admin-dashboard': AdminDashboard,
    'field-agent-dashboard': FieldAgentDashboard,
};

document.querySelectorAll('[data-react-app]').forEach((element) => {
    const Component = components[element.dataset.reactApp];

    if (!Component) {
        return;
    }

    const agent = element.dataset.agent
        ? JSON.parse(element.dataset.agent)
        : {};

    createRoot(element).render(
        createElement(Component, {
            partnerName: element.dataset.partnerName,
            adminName: element.dataset.adminName,
            agent,

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
