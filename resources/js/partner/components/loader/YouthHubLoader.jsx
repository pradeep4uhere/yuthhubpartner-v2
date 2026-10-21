import "./YouthHubLoader.css";

export default function YouthHubLoader({
    loading = true,
    variant = "content",
    message = "Loading, please wait...",
    title = "YouthHub Partner",
    size = 150,
    className = "",
}) {
    if (!loading) return null;

    return (
        <div
            className={`yhl-loader yhl-loader--${variant} ${className}`}
            role="status"
            aria-live="polite"
            aria-label={message}
        >
            <div className="yhl-loader__content">
                <img
                    src="/YouthHub_Partner_Loader.gif"
                    alt=""
                    className="yhl-loader__gif"
                    style={{ width: size, height: size }}
                />

                {title && <h3 className="yhl-loader__title">{title}</h3>}

                {message && <p className="yhl-loader__message">{message}</p>}

                <div className="yhl-loader__dots" aria-hidden="true">
                    <span />
                    <span />
                    <span />
                </div>
            </div>
        </div>
    );
}
