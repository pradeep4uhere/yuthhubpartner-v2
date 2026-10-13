
import { ArrowUpRight } from 'lucide-react';

const pipelineStages = [
    {
        label: 'Mobilised',
        count: '1,284',
        percent: 100,
        tone: 'indigo',
    },
    {
        label: 'Onboarded',
        count: '1,046',
        percent: 81,
        tone: 'blue',
    },
    {
        label: 'In training',
        count: '846',
        percent: 66,
        tone: 'amber',
    },
    {
        label: 'Certified',
        count: '512',
        percent: 40,
        tone: 'green',
    },
    {
        label: 'Placed',
        count: '238',
        percent: 19,
        tone: 'coral',
    },
];

export default function LearningPipeline() {
    return (
        <article className="admin-panel admin-pipeline" id="learning">
            <div className="admin-panel__heading">
                <div>
                    <h2>Learning pipeline</h2>
                    <p>1,284 learners across all stages</p>
                </div>
            </div>

            <div className="admin-pipeline__list">
                {pipelineStages.map((stage) => (
                    <div
                        className="admin-pipeline__row"
                        key={stage.label}
                    >
                        <span
                            className={`pipeline-marker pipeline-marker--${stage.tone}`}
                        />

                        <span className="admin-pipeline__label">
                            {stage.label}
                        </span>

                        <b>{stage.count}</b>

                        <span className="admin-pipeline__percent">
                            {stage.percent}%
                        </span>

                        <div className="admin-pipeline__track">
                            <i style={{ width: `${stage.percent}%` }} />
                        </div>
                    </div>
                ))}
            </div>

            <button
                className="admin-text-link"
                onClick={() => {
                    window.location.href = '/admin/learners';
                }}
            >
                Explore learner pipeline <ArrowUpRight size={14} />
            </button>
        </article>
    );
}
