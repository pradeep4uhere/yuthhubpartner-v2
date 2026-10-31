import { Building2 } from "lucide-react";
export default function AssociatedPartners() {
    return (
        <div className="yp-content">
            <section className="yp-panel">
                <div className="yp-panel-heading">
                    <h2>
                        <Building2 size={18} />
                        Associated Partners
                    </h2>
                </div>
                <p>
                    Only partners linked to your Placement Partner account will
                    appear here. The association API and workspace authorization
                    are the next implementation step.
                </p>
                <div className="yp-empty">No partner records loaded yet.</div>
            </section>
        </div>
    );
}
