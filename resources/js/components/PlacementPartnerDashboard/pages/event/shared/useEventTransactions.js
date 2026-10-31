
import { useEffect, useState } from "react";

const API_BASE = "/api/partner-placement";

/**
 * Fetches Event Transactions using a configurable API endpoint.
 * Supports both the main listing and Field Agent details response formats.
 */
export default function useEventTransactions({
    agentId = null,
    page = 1,
    perPage = 10,
    search = "",
    status = "",
    advancedFilters = {},
    refreshKey = 0,
}) {
    const [transactions, setTransactions] = useState([]);
    const [agent, setAgent] = useState(null);
    const [summary, setSummary] = useState({});
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: perPage,
        total: 0,
    });

    const [loading, setLoading] = useState(true);
    const [initialLoading, setInitialLoading] = useState(true);
    const [error, setError] = useState("");

    const isAgentView = agentId !== null && agentId !== undefined;

    // Stabilize the advanced-filter dependency using its supported fields.
    const advancedKey = JSON.stringify(advancedFilters);

    /**
     * Loads the authorized transaction listing and normalizes pagination.
     */
    useEffect(() => {
        const controller = new AbortController();

        /**
         * Requests transaction data from the appropriate Placement Partner API.
         */
        const loadTransactions = async () => {
            setLoading(true);
            setError("");

            const params = new URLSearchParams({
                page: String(page),
                per_page: String(perPage),
            });

            if (search.trim()) {
                params.set("search", search.trim());
            }
            if (isAgentView) {
                if (status) {
                    params.set("status", status);
                }

                // Append applied Advanced Search filters.
                Object.entries(JSON.parse(advancedKey)).forEach(([key, value]) => {
                    if (String(value ?? "").trim() !== "") {
                        params.set(key, String(value).trim());
                    }
                });
            }else {
                Object.entries(JSON.parse(advancedKey)).forEach(
                    ([key, value]) => {
                        if (String(value ?? "").trim()) {
                            params.set(key, String(value).trim());
                        }
                    },
                );
            }

            const endpoint = isAgentView
                ? `${API_BASE}/field-agents/${encodeURIComponent(agentId)}/transactions`
                : `${API_BASE}/event-transactions`;

            try {
                const response = await fetch(
                    `${endpoint}?${params.toString()}`,
                    {
                        method: "GET",
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        signal: controller.signal,
                    },
                );

                const result = await response.json();

                if (!response.ok || result.success !== true) {
                    throw new Error(
                        result.message || "Unable to load transactions.",
                    );
                }

                const payload = result.data ?? {};

                // Field Agent API returns a Laravel paginator inside data.
                // Main Event Transactions API returns a flat data array.
                const records = isAgentView
                    ? payload.transactions?.data
                    : result.data;

                const paging = isAgentView
                    ? payload.transactions
                    : result.pagination;

                if (controller.signal.aborted) return;

                setTransactions(
                    Array.isArray(records) ? records : [],
                );

                setAgent(isAgentView ? payload.agent ?? null : null);

                setSummary(
                    isAgentView
                        ? payload.summary ?? {}
                        : result.summary ?? {},
                );

                setPagination({
                    current_page: Number(paging?.current_page ?? page),
                    last_page: Math.max(
                        1,
                        Number(paging?.last_page ?? 1),
                    ),
                    per_page: Number(paging?.per_page ?? perPage),
                    total: Number(paging?.total ?? 0),
                });
            } catch (err) {
                if (err.name !== "AbortError" && !controller.signal.aborted) {
                    setError(err.message || "Unable to load transactions.");
                    setTransactions([]);
                    setAgent(null);
                    setSummary({});
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                    setInitialLoading(false);
                }
            }
        };

        loadTransactions();

        return () => controller.abort();
    }, [
        agentId,
        isAgentView,
        page,
        perPage,
        search,
        status,
        advancedKey,
        refreshKey,
    ]);

    return {
        transactions,
        agent,
        summary,
        pagination,
        loading,
        initialLoading,
        error,
    };
}
