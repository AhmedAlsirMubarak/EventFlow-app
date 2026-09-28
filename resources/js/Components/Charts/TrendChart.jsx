import { useEffect, useMemo, useRef, useState } from 'react';

const PLOT_HEIGHT = 160;
const AXIS_BAND = 24; // room for the x-axis labels, so they're never clipped
const LEFT_GUTTER = 32;
const TOP_PAD = 8;

// Round the axis max up to a "nice" 1/2/5 × 10^n value, so gridlines land on
// readable numbers.
function niceMax(value) {
    if (value <= 4) {
        return 4;
    }

    const magnitude = 10 ** Math.floor(Math.log10(value));
    const step = [1, 2, 5, 10].find((n) => n * magnitude >= value);

    return step * magnitude;
}

function formatDay(date, options = { month: 'short', day: 'numeric' }) {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString(undefined, options);
}

/**
 * Single-series trend (line + soft area) with a hover/keyboard crosshair and
 * tooltip. `points`: [{ date: 'YYYY-MM-DD', count }]. One series, so no legend —
 * the card title names it.
 */
export default function TrendChart({ points, unit = 'activity', unitPlural = 'activities' }) {
    const containerRef = useRef(null);
    const [width, setWidth] = useState(0);
    const [active, setActive] = useState(null);

    useEffect(() => {
        const element = containerRef.current;

        if (!element) {
            return undefined;
        }

        const observer = new ResizeObserver(([entry]) => setWidth(entry.contentRect.width));
        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    const max = niceMax(Math.max(0, ...points.map((point) => point.count)));
    const plotWidth = Math.max(0, width - LEFT_GUTTER);
    const step = points.length > 1 ? plotWidth / (points.length - 1) : 0;

    const coords = useMemo(
        () =>
            points.map((point, index) => ({
                x: LEFT_GUTTER + index * step,
                y: TOP_PAD + PLOT_HEIGHT - (point.count / max) * PLOT_HEIGHT,
            })),
        [points, step, max],
    );

    const line = coords.map((c, i) => `${i === 0 ? 'M' : 'L'}${c.x},${c.y}`).join(' ');
    const baseline = TOP_PAD + PLOT_HEIGHT;
    const area = coords.length ? `${line} L${coords.at(-1).x},${baseline} L${coords[0].x},${baseline} Z` : '';
    const labelIndexes = [0, Math.floor((points.length - 1) / 2), points.length - 1];

    function pick(clientX) {
        const box = containerRef.current.getBoundingClientRect();
        const index = Math.round((clientX - box.left - LEFT_GUTTER) / (step || 1));
        setActive(Math.min(points.length - 1, Math.max(0, index)));
    }

    function onKeyDown(e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            e.preventDefault();
            setActive((current) => {
                const from = current ?? points.length - 1;

                return Math.min(points.length - 1, Math.max(0, from + (e.key === 'ArrowLeft' ? -1 : 1)));
            });
        }
    }

    const activePoint = active !== null ? points[active] : null;
    const activeCoord = active !== null ? coords[active] : null;

    return (
        <div
            ref={containerRef}
            className="relative w-full select-none rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40"
            style={{ height: TOP_PAD + PLOT_HEIGHT + AXIS_BAND }}
            tabIndex={0}
            role="img"
            aria-label={`Daily ${unitPlural}, last ${points.length} days. Use the arrow keys to read individual days.`}
            onMouseMove={(e) => pick(e.clientX)}
            onMouseLeave={() => setActive(null)}
            onFocus={() => setActive((current) => current ?? points.length - 1)}
            onBlur={() => setActive(null)}
            onKeyDown={onKeyDown}
        >
            {width > 0 && (
                <svg width={width} height={TOP_PAD + PLOT_HEIGHT + AXIS_BAND} aria-hidden="true">
                    <defs>
                        <linearGradient id="trend-fill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="#4f46e5" stopOpacity="0.18" />
                            <stop offset="100%" stopColor="#4f46e5" stopOpacity="0" />
                        </linearGradient>
                    </defs>

                    {[0, max / 2, max].map((tick) => {
                        const y = TOP_PAD + PLOT_HEIGHT - (tick / max) * PLOT_HEIGHT;

                        return (
                            <g key={tick}>
                                <line x1={LEFT_GUTTER} x2={width} y1={y} y2={y} stroke="#f1f5f9" strokeWidth="1" />
                                <text
                                    x={LEFT_GUTTER - 8}
                                    y={y}
                                    textAnchor="end"
                                    dominantBaseline="middle"
                                    className="fill-slate-400 text-[11px] tabular-nums"
                                >
                                    {Number.isInteger(tick) ? tick : tick.toFixed(1)}
                                </text>
                            </g>
                        );
                    })}

                    <path d={area} fill="url(#trend-fill)" />
                    <path
                        d={line}
                        fill="none"
                        stroke="#4f46e5"
                        strokeWidth="2"
                        strokeLinejoin="round"
                        strokeLinecap="round"
                    />

                    {labelIndexes.map((index, position) => (
                        <text
                            key={index}
                            x={coords[index].x}
                            y={baseline + 17}
                            textAnchor={position === 0 ? 'start' : position === 2 ? 'end' : 'middle'}
                            className="fill-slate-400 text-[11px]"
                        >
                            {formatDay(points[index].date)}
                        </text>
                    ))}

                    {activeCoord && (
                        <>
                            <line
                                x1={activeCoord.x}
                                x2={activeCoord.x}
                                y1={TOP_PAD}
                                y2={baseline}
                                stroke="#cbd5e1"
                                strokeWidth="1"
                            />
                            <circle cx={activeCoord.x} cy={activeCoord.y} r="5" fill="#4f46e5" stroke="#fff" strokeWidth="2" />
                        </>
                    )}
                </svg>
            )}

            {activePoint && activeCoord && (
                <div
                    className="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-slate-900 px-3 py-2 text-xs text-white shadow-lg"
                    style={{
                        left: Math.min(Math.max(activeCoord.x, 70), width - 70),
                        top: Math.max(activeCoord.y - 12, 44),
                    }}
                >
                    <p className="font-medium text-slate-300">
                        {formatDay(activePoint.date, { weekday: 'short', month: 'short', day: 'numeric' })}
                    </p>
                    <p className="mt-0.5 font-semibold">
                        {activePoint.count} {activePoint.count === 1 ? unit : unitPlural}
                    </p>
                </div>
            )}
        </div>
    );
}
