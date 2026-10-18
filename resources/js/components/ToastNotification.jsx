import React, { useEffect } from "react";
import "./ToastNotification.css";

export default function ToastNotification({
    type = "success",
    title,
    message,
    onClose,
}) {
    useEffect(() => {
        const timer = setTimeout(() => {
            onClose?.();
        }, 4500);

        return () => clearTimeout(timer);
    }, [onClose]);

    const isSuccess = type === "success";

    return (
        <div
            className={`modern-toast modern-toast--${type}`}
            role="status"
            aria-live="polite"
        >
            <div className="modern-toast__accent" />

            <div className="modern-toast__icon">
                {isSuccess ? (
                    <svg viewBox="0 0 24 24" fill="none">
                        <path
                            d="M5 12.5L10 17L19 7"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                ) : (
                    <svg viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 8V13"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                        />
                        <circle cx="12" cy="17" r="1.3" fill="currentColor" />
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke="currentColor"
                            strokeWidth="2"
                        />
                    </svg>
                )}
            </div>

            <div className="modern-toast__content">
                <div className="modern-toast__eyebrow">
                    {isSuccess ? "COMPLETED SUCCESSFULLY" : "ACTION REQUIRED"}
                </div>

                <div className="modern-toast__title">
                    {title || (isSuccess ? "Success!" : "Something went wrong")}
                </div>

                <div className="modern-toast__message">{message}</div>
            </div>

            <button
                type="button"
                className="modern-toast__close"
                onClick={onClose}
                aria-label="Close notification"
            >
                ×
            </button>

            <div className="modern-toast__progress" />
        </div>
    );
}
