/**
 * Status Badges Component
 * Display various status indicators and badges for pipeline items
 */

import React from 'react';
import { StatusBadge } from '../types/Pipeline';

interface StatusBadgesProps {
  badges: StatusBadge[];
  maxVisible?: number;
  size?: 'xs' | 'sm' | 'md';
  className?: string;
}

const StatusBadges: React.FC<StatusBadgesProps> = ({
  badges,
  maxVisible = 3,
  size = 'sm',
  className = '',
}) => {
  const visibleBadges = badges.slice(0, maxVisible);
  const hiddenCount = Math.max(0, badges.length - maxVisible);

  // Size configurations
  const sizeConfig = {
    xs: {
      text: 'text-xs',
      padding: 'px-1.5 py-0.5',
      icon: 'w-3 h-3'
    },
    sm: {
      text: 'text-xs',
      padding: 'px-2 py-1',
      icon: 'w-3 h-3'
    },
    md: {
      text: 'text-sm',
      padding: 'px-3 py-1.5',
      icon: 'w-4 h-4'
    }
  };

  const config = sizeConfig[size];

  // Color configurations for badge types
  const colorConfig = {
    priority: {
      urgent: 'bg-red-100 text-red-800 border-red-200',
      high: 'bg-orange-100 text-orange-800 border-orange-200',
      medium: 'bg-blue-100 text-blue-800 border-blue-200',
      low: 'bg-gray-100 text-gray-800 border-gray-200'
    },
    status: {
      overdue: 'bg-red-100 text-red-800 border-red-200',
      at_risk: 'bg-yellow-100 text-yellow-800 border-yellow-200',
      on_track: 'bg-green-100 text-green-800 border-green-200',
      completed: 'bg-green-100 text-green-800 border-green-200'
    },
    type: {
      high_value: 'bg-emerald-100 text-emerald-800 border-emerald-200',
      follow_up: 'bg-blue-100 text-blue-800 border-blue-200',
      approval_needed: 'bg-purple-100 text-purple-800 border-purple-200',
      client_waiting: 'bg-indigo-100 text-indigo-800 border-indigo-200'
    }
  };

  // Icon mapping
  const iconMap = {
    'exclamation-triangle': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
    ),
    'clock': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    'arrow-up': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 11l5-5m0 0l5 5m-5-5v12" />
      </svg>
    ),
    'dollar-sign': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
      </svg>
    ),
    'phone': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
      </svg>
    ),
    'warning': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
    ),
    'check-circle': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    'user-group': (
      <svg className={config.icon} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
    )
  };

  // Get badge color classes
  const getBadgeClasses = (badge: StatusBadge): string => {
    let colorClasses = '';

    // Determine color based on badge type and color
    if (badge.type === 'priority') {
      colorClasses = colorConfig.priority[badge.color as keyof typeof colorConfig.priority] || colorConfig.priority.medium;
    } else if (['overdue', 'at_risk', 'on_track', 'completed'].includes(badge.type)) {
      colorClasses = colorConfig.status[badge.type as keyof typeof colorConfig.status] || colorConfig.status.on_track;
    } else {
      colorClasses = colorConfig.type[badge.type as keyof typeof colorConfig.type] || 'bg-gray-100 text-gray-800 border-gray-200';
    }

    return `inline-flex items-center ${config.padding} ${config.text} font-medium rounded-full border ${colorClasses}`;
  };

  if (badges.length === 0) {
    return null;
  }

  return (
    <div className={`status-badges flex flex-wrap gap-1 ${className}`}>
      {visibleBadges.map((badge, index) => (
        <span
          key={`${badge.type}-${index}`}
          className={getBadgeClasses(badge)}
          title={badge.tooltip}
        >
          {badge.icon && iconMap[badge.icon as keyof typeof iconMap] && (
            <span className="mr-1">
              {iconMap[badge.icon as keyof typeof iconMap]}
            </span>
          )}
          {badge.text}
        </span>
      ))}

      {/* Show count of hidden badges */}
      {hiddenCount > 0 && (
        <span 
          className={`inline-flex items-center ${config.padding} ${config.text} font-medium rounded-full border bg-gray-100 text-gray-600 border-gray-200`}
          title={`${hiddenCount} more badge${hiddenCount !== 1 ? 's' : ''}`}
        >
          +{hiddenCount}
        </span>
      )}
    </div>
  );
};

// Priority Badge Component - specialized for priority display
interface PriorityBadgeProps {
  priority: 'low' | 'medium' | 'high' | 'urgent';
  size?: 'xs' | 'sm' | 'md';
  showIcon?: boolean;
  className?: string;
}

export const PriorityBadge: React.FC<PriorityBadgeProps> = ({
  priority,
  size = 'sm',
  showIcon = true,
  className = '',
}) => {
  const priorityConfig = {
    urgent: {
      color: 'bg-red-100 text-red-800 border-red-200',
      icon: 'exclamation-triangle',
      label: 'URGENT'
    },
    high: {
      color: 'bg-orange-100 text-orange-800 border-orange-200',
      icon: 'arrow-up',
      label: 'HIGH'
    },
    medium: {
      color: 'bg-blue-100 text-blue-800 border-blue-200',
      icon: 'minus',
      label: 'MEDIUM'
    },
    low: {
      color: 'bg-gray-100 text-gray-600 border-gray-200',
      icon: 'arrow-down',
      label: 'LOW'
    }
  };

  const config = priorityConfig[priority];
  const sizeConfig = {
    xs: 'text-xs px-1.5 py-0.5',
    sm: 'text-xs px-2 py-1',
    md: 'text-sm px-3 py-1.5'
  };

  return (
    <span className={`inline-flex items-center ${sizeConfig[size]} font-semibold rounded-full border ${config.color} ${className}`}>
      {showIcon && (
        <span className="mr-1">
          {priority === 'urgent' && '🔥'}
          {priority === 'high' && '⬆️'}
          {priority === 'medium' && '➡️'}
          {priority === 'low' && '⬇️'}
        </span>
      )}
      {config.label}
    </span>
  );
};

// Stage Badge Component - specialized for stage display
interface StageBadgeProps {
  stage: string;
  size?: 'xs' | 'sm' | 'md';
  showIcon?: boolean;
  className?: string;
}

export const StageBadge: React.FC<StageBadgeProps> = ({
  stage,
  size = 'sm',
  showIcon = true,
  className = '',
}) => {
  const stageConfig = {
    quote_requested: {
      color: 'bg-gray-100 text-gray-800 border-gray-200',
      icon: '📝',
      label: 'Quote Requested'
    },
    quote_prepared: {
      color: 'bg-blue-100 text-blue-800 border-blue-200',
      icon: '📋',
      label: 'Quote Prepared'
    },
    quote_sent: {
      color: 'bg-purple-100 text-purple-800 border-purple-200',
      icon: '📤',
      label: 'Quote Sent'
    },
    quote_approved: {
      color: 'bg-yellow-100 text-yellow-800 border-yellow-200',
      icon: '✅',
      label: 'Quote Approved'
    },
    order_processing: {
      color: 'bg-orange-100 text-orange-800 border-orange-200',
      icon: '⚙️',
      label: 'Processing'
    },
    ready_to_ship: {
      color: 'bg-green-100 text-green-800 border-green-200',
      icon: '📦',
      label: 'Ready to Ship'
    },
    invoiced_delivered: {
      color: 'bg-emerald-100 text-emerald-800 border-emerald-200',
      icon: '🚚',
      label: 'Delivered'
    }
  };

  const config = stageConfig[stage as keyof typeof stageConfig] || stageConfig.quote_requested;
  const sizeConfig = {
    xs: 'text-xs px-1.5 py-0.5',
    sm: 'text-xs px-2 py-1',
    md: 'text-sm px-3 py-1.5'
  };

  return (
    <span className={`inline-flex items-center ${sizeConfig[size]} font-medium rounded-full border ${config.color} ${className}`}>
      {showIcon && (
        <span className="mr-1">
          {config.icon}
        </span>
      )}
      {config.label}
    </span>
  );
};

export default StatusBadges;
