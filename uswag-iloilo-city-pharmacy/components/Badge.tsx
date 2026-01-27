
import React from 'react';
import { RequisitionStatus, POStatus, NoticeOfIssuanceStatus, IssuanceStatus } from '../types';

interface BadgeProps {
  status: RequisitionStatus | POStatus | NoticeOfIssuanceStatus | IssuanceStatus;
}

const Badge: React.FC<BadgeProps> = ({ status }) => {
  let colorClass = '';

  switch (status) {
    case 'Pending':
      colorClass = 'badge-warning';
      break;
    case 'Partial':
      colorClass = 'badge-orange';
      break;
    case 'Approved':
      colorClass = 'badge-success';
      break;
    case 'Rejected':
      colorClass = 'badge-danger';
      break;
    case 'Processed':
    case 'Completed':
      colorClass = 'badge-info';
      break;
    default:
      colorClass = 'badge-secondary';
  }

  return <span className={`badge ${colorClass}`}>{status}</span>;
};

export default Badge;
