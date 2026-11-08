import type { ScoringSummaryDto } from '../../types/credit.types';
import { formatBpsToPercent, formatCentsToEuros } from '../../utils/moneyFormatter';

interface ScoringBadgePanelProps {
  summary: ScoringSummaryDto;
  /** Affiche le détail des points du score (vue conseiller). */
  showBreakdown?: boolean;
}

/** Échelle de la jauge d'endettement : 0 à 50 %. */
const GAUGE_MAX_BPS = 5_000;
/** Repère du plafond le plus haut du catalogue (35 %). */
const CEILING_BPS = 3_500;

const gaugeWidth = (bps: number): string => `${Math.min(100, Math.max(0, Math.round((bps * 100) / GAUGE_MAX_BPS)))}%`;

/**
 * Résumé du scoring : grade A à E, jauge d'endettement avant / après, reste à vivre.
 */
export const ScoringBadgePanel = ({ summary, showBreakdown = false }: ScoringBadgePanelProps) => {
  const grade = summary.riskGrade.slice(-1);
  const remainderOk = summary.remainderToLiveCents >= summary.minRemainderToLiveCents;

  return (
    <section className="scoring-panel" aria-label="Analyse de votre situation">
      <div className={`grade-badge grade-${grade.toLowerCase()}`}>
        <span className="grade-letter">{grade}</span>
        <span className="grade-label">Profil</span>
      </div>

      <div className="scoring-metrics">
        <div className="gauge">
          <div className="gauge-head">
            <span>Taux d'endettement</span>
            <strong>
              {formatBpsToPercent(summary.debtRatioBeforeBps, 1)} → {formatBpsToPercent(summary.debtRatioAfterBps, 1)}
            </strong>
          </div>
          <div className="gauge-track" role="img" aria-label={`Endettement actuel ${formatBpsToPercent(summary.debtRatioBeforeBps, 1)}, après crédit ${formatBpsToPercent(summary.debtRatioAfterBps, 1)}`}>
            <span className={`gauge-after${summary.debtRatioAfterBps > CEILING_BPS ? ' is-over' : ''}`} style={{ width: gaugeWidth(summary.debtRatioAfterBps) }} />
            <span className="gauge-before" style={{ width: gaugeWidth(summary.debtRatioBeforeBps) }} />
            <span className="gauge-ceiling" style={{ left: gaugeWidth(CEILING_BPS) }} />
          </div>
          <div className="gauge-legend">
            <span><i className="dot dot-before" /> Avant</span>
            <span><i className="dot dot-after" /> Après la nouvelle mensualité</span>
            <span>Plafond 35 %</span>
          </div>
        </div>

        <dl className="scoring-facts">
          <div>
            <dt>Reste à vivre</dt>
            <dd className={remainderOk ? 'is-ok' : 'is-low'}>{formatCentsToEuros(summary.remainderToLiveCents, { withCents: false })}/mois</dd>
          </div>
          <div>
            <dt>Minimum pour votre foyer</dt>
            <dd>{formatCentsToEuros(summary.minRemainderToLiveCents, { withCents: false })}/mois</dd>
          </div>
          <div>
            <dt>Revenus retenus</dt>
            <dd>{formatCentsToEuros(summary.weightedIncomeCents, { withCents: false })}/mois</dd>
          </div>
          <div>
            <dt>Score</dt>
            <dd>{summary.score}/1000</dd>
          </div>
        </dl>

        {showBreakdown && summary.scoreBreakdown.length > 0 ? (
          <table className="score-breakdown">
            <caption>Détail du score (base 500)</caption>
            <tbody>
              {summary.scoreBreakdown.map((item) => (
                <tr key={item.criterion}>
                  <th scope="row">{item.label}</th>
                  <td className={item.points < 0 ? 'is-negative' : 'is-positive'}>
                    {item.points > 0 ? '+' : ''}
                    {item.points}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : null}
      </div>
    </section>
  );
};
