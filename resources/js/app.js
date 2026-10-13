import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import PartnerWorkspace from './components/PartnerWorkspace.jsx';
import AdminDashboard from './components/AdminDashboard.jsx';

const components = {
	'partner-workspace': PartnerWorkspace,
	'admin-dashboard': AdminDashboard,
};

document.querySelectorAll('[data-react-app]').forEach((element) => {
	const Component = components[element.dataset.reactApp];
	if (Component) {
		createRoot(element).render(
            createElement(Component, {
                partnerName: element.dataset.partnerName,
                adminName: element.dataset.adminName,

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
            }),
        );
	}
});
