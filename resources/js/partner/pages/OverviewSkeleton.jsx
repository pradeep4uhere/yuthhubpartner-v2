import "./OverviewSkeleton.css";

export function Skeleton({
    width = "100%",
    height = 12,
    circle = false,
    className = "",
}) {
    return (
        <span
            className={`ovs-skeleton ${
                circle ? "ovs-skeleton--circle" : ""
            } ${className}`}
            style={{ width, height }}
            aria-hidden="true"
        />
    );
}

/* Summary Cards */

export function OverviewStatsSkeleton() {
    const cards = ["blue", "green", "amber"];

    return (
        <>
            {cards.map((tone) => (
                <article
                    key={tone}
                    className={`yp-stat ovs-stat ovs-stat--${tone}`}
                    aria-label="Loading dashboard statistics"
                >
                    <Skeleton width={40} height={40} circle />

                    <div className="ovs-stat__content">
                        <Skeleton width="105px" height={11} />
                        <Skeleton width="80px" height={24} />
                        <Skeleton width="135px" height={9} />
                    </div>

                    <Skeleton width={23} height={23} circle />
                </article>
            ))}
        </>
    );
}

/* Growth Chart Skeleton */

export function OverviewGrowthSkeleton() {
    return (
        <div
            className="ovs-growth"
            role="status"
            aria-label="Loading growth analytics"
        >
            <div className="ovs-growth__legend">
                {[70, 95, 120].map((width, index) => (
                    <div key={index} className="ovs-legend-item">
                        <Skeleton width={9} height={9} circle />
                        <Skeleton width={width} height={9} />
                    </div>
                ))}
            </div>

            <div className="ovs-chart">
                <div className="ovs-chart__axis">
                    {[1, 2, 3, 4].map((item) => (
                        <Skeleton key={item} width={25} height={9} />
                    ))}
                </div>

                <div className="ovs-chart__body">
                    <div className="ovs-chart__grid">
                        {[1, 2, 3, 4].map((item) => (
                            <div key={item} />
                        ))}
                    </div>

                    <div className="ovs-chart__bars">
                        {[42, 58, 47, 76, 62, 88].map((height, index) => (
                            <Skeleton
                                key={index}
                                width="10%"
                                height={`${height}%`}
                                className="ovs-chart__bar"
                            />
                        ))}
                    </div>
                </div>
            </div>

            <div className="ovs-chart__months">
                {Array.from({ length: 6 }, (_, index) => (
                    <Skeleton key={index} width={34} height={9} />
                ))}
            </div>
        </div>
    );
}

/* Transaction Donut Skeleton */

export function OverviewDonutSkeleton() {
    return (
        <div
            className="ovs-donut-layout"
            role="status"
            aria-label="Loading transaction breakdown"
        >
            <div className="ovs-donut">
                <div className="ovs-donut__hole">
                    <Skeleton width={45} height={19} />
                    <Skeleton width={32} height={9} />
                </div>
            </div>

            <div className="ovs-donut__legend">
                {[1, 2, 3, 4].map((item) => (
                    <div className="ovs-donut__item" key={item}>
                        <Skeleton width={9} height={9} circle />
                        <Skeleton width="65%" height={10} />
                        <Skeleton width={28} height={10} />
                    </div>
                ))}
            </div>
        </div>
    );
}
