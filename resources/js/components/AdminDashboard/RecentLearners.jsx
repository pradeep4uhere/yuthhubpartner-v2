
import { useMemo, useState } from 'react';
import {
    ArrowDownToLine,
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    Search,
    X,
} from 'lucide-react';

import { exportLearners } from './helpers';

export default function RecentLearners({
    learners = [],
    showNotice,
}) {
    const [query, setQuery] = useState('');

    const filteredLearners = useMemo(() => {
        const search = query.trim().toLowerCase();

        return learners.filter((learner) =>
            [
                learner.first_name,
                learner.last_name,
                learner.program_code,
                learner.unit,
                learner.location,
            ].some((value) =>
                String(value ?? '').toLowerCase().includes(search)
            )
        );
    }, [learners, query]);

    return (
        <article className="admin-panel admin-learners" id="learners">
            <div className="admin-panel__heading admin-learners__heading">
                <div>
                    <h2>Recently updated learners</h2>
                    <p>Keep track of the latest learner activity</p>
                </div>

                <div className="admin-table-actions">
                    <label className="admin-search">
                        <Search size={15} />

                        <input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search learners"
                            aria-label="Search learners"
                        />

                        {query && (
                            <button
                                type="button"
                                onClick={() => setQuery('')}
                                aria-label="Clear search"
                            >
                                <X size={13} />
                            </button>
                        )}
                    </label>

                    <button
                        className="admin-export"
                        onClick={() => exportLearners(filteredLearners)}
                    >
                        <ArrowDownToLine size={15} />
                        <span>Export</span>
                    </button>
                </div>
            </div>

            <div className="admin-table-scroll">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>Learner</th>
                            <th>Programme</th>
                            <th>Unit Institute</th>
                            <th>Last updated</th>
                            <th>
                                <span className="visually-hidden">Actions</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {filteredLearners.map((learner) => (
                            <tr key={learner.id}>
                                <td>
                                    <div className="learner-cell">
                                        <span className="learner-avatar">
                                            {[
                                                learner.first_name,
                                                learner.last_name,
                                            ]
                                                .filter(Boolean)
                                                .map((name) => name[0])
                                                .join('')
                                                .toUpperCase()}
                                        </span>

                                        <span>
                                            <b>{learner.first_name}</b>
                                            <small>{learner.location}</small>
                                        </span>
                                    </div>
                                </td>

                                <td>{learner.program_code}</td>
                                <td>{learner.unit}</td>
                                <td className="admin-table__date">
                                    {learner.source_created_at}
                                </td>

                                <td>
                                    <button
                                        className="admin-icon-button admin-row-more"
                                        aria-label={`More actions for ${learner.first_name}`}
                                        onClick={() =>
                                            showNotice(
                                                `${learner.first_name}'s profile is not connected yet.`
                                            )
                                        }
                                    >
                                        <MoreHorizontal size={18} />
                                    </button>
                                </td>
                            </tr>
                        ))}

                        {filteredLearners.length === 0 && (
                            <tr>
                                <td className="admin-empty" colSpan="5">
                                    No learners match “{query}”.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="admin-table-footer">
                <span>
                    Showing <b>{filteredLearners.length}</b> of{' '}
                    <b>{learners.length}</b> loaded learners
                </span>

                <div>
                    <button
                        className="admin-icon-button"
                        aria-label="Previous page"
                        disabled
                    >
                        <ChevronLeft size={17} />
                    </button>

                    <span className="admin-page-count">Latest records</span>

                    <button
                        className="admin-icon-button"
                        aria-label="Next page"
                        disabled
                    >
                        <ChevronRight size={17} />
                    </button>
                </div>
            </div>
        </article>
    );
}
