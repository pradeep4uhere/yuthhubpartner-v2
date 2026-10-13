export default function DashboardHeader({ adminName = 'Admin' }) {
    const getGreeting = () => {
        const hour = new Date().getHours();

        if (hour < 12) return 'Good Morning';
        if (hour < 17) return 'Good Afternoon';

        return 'Good Evening';
    };

    return (
        <header className="admin-topbar">
            <div className="admin-breadcrumb">
                <span>Admin Workspace</span>
                <b>{getGreeting()}, {adminName}</b>
            </div>

            <div className="admin-topbar__actions">
                <span className="admin-demo-label">
                    <span /> Notification
                </span>

                <form
                    method="POST"
                    action="/admin/logout"
                    className="admin-logout-form"
                >
                    <input
                        type="hidden"
                        name="_token"
                        value={
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.content ?? ''
                        }
                    />

                    <button
                        className="admin-topbar__settings"
                        type="submit"
                    >
                        Sign out
                    </button>
                </form>
            </div>
        </header>
    );
}