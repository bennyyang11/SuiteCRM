/**
 * Pipeline Card Component
 * Individual order card with drag-and-drop support and status indicators
 */

import React, { useMemo } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { OrderPipelineItem, StatusBadge } from '../types/Pipeline';
import ProgressIndicator from './ProgressIndicator';
import StatusBadges from './StatusBadges';
import UserAvatar from './UserAvatar';

interface PipelineCardProps {
  order: OrderPipelineItem;
  onClick: (orderId: string) => void;
  isDragging?: boolean;
  className?: string;
}

const PipelineCard: React.FC<PipelineCardProps> = ({
  order,
  onClick,
  isDragging = false,
  className = '',
}) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging: sortableIsDragging,
  } = useSortable({
    id: order.id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  // Generate status badges
  const statusBadges = useMemo((): StatusBadge[] => {
    const badges: StatusBadge[] = [];

    // Priority badge
    if (order.priority === 'urgent') {
      badges.push({
        type: 'priority',
        color: 'red',
        icon: 'exclamation-triangle',
        text: 'URGENT',
        tooltip: 'High priority order requiring immediate attention'
      });
    } else if (order.priority === 'high') {
      badges.push({
        type: 'priority',
        color: 'orange',
        icon: 'arrow-up',
        text: 'HIGH',
        tooltip: 'High priority order'
      });
    }

    // Overdue badge
    if (order.isOverdue) {
      badges.push({
        type: 'overdue',
        color: 'red',
        icon: 'clock',
        text: 'OVERDUE',
        tooltip: `${order.daysInStage} days in current stage`
      });
    } else if (order.daysInStage > 7) {
      badges.push({
        type: 'at_risk',
        color: 'yellow',
        icon: 'warning',
        text: 'AT RISK',
        tooltip: `${order.daysInStage} days in current stage - may become overdue`
      });
    }

    // Value badge for high-value orders
    if (order.totalValue > 50000) {
      badges.push({
        type: 'high_value',
        color: 'green',
        icon: 'dollar-sign',
        text: 'HIGH VALUE',
        tooltip: `Order value: $${order.totalValue.toLocaleString()}`
      });
    }

    // Client approval status
    if (order.currentStage === 'quote_sent' && order.quoteSentDate) {
      const daysSent = Math.floor((Date.now() - new Date(order.quoteSentDate).getTime()) / (1000 * 60 * 60 * 24));
      if (daysSent > 5) {
        badges.push({
          type: 'follow_up',
          color: 'blue',
          icon: 'phone',
          text: 'FOLLOW UP',
          tooltip: `Quote sent ${daysSent} days ago - consider follow-up`
        });
      }
    }

    return badges;
  }, [order]);

  // Calculate completion percentage
  const completionPercentage = useMemo(() => {
    const stageOrder = {
      'quote_requested': 1,
      'quote_prepared': 2,
      'quote_sent': 3,
      'quote_approved': 4,
      'order_processing': 5,
      'ready_to_ship': 6,
      'invoiced_delivered': 7
    };
    
    const currentStageOrder = stageOrder[order.currentStage as keyof typeof stageOrder] || 1;
    return Math.round((currentStageOrder / 7) * 100);
  }, [order.currentStage]);

  // Format currency
  const formatCurrency = (value: number) => {
    if (value >= 1000000) {
      return `$${(value / 1000000).toFixed(1)}M`;
    } else if (value >= 1000) {
      return `$${(value / 1000).toFixed(1)}K`;
    } else {
      return `$${value.toLocaleString()}`;
    }
  };

  // Handle card click
  const handleClick = (e: React.MouseEvent) => {
    if (!sortableIsDragging && !isDragging) {
      e.preventDefault();
      e.stopPropagation();
      onClick(order.id);
    }
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className={`
        pipeline-card bg-white rounded-lg border border-gray-200 shadow-sm
        cursor-pointer transition-all duration-200 hover:shadow-md hover:border-gray-300
        ${sortableIsDragging || isDragging ? 'opacity-50 rotate-2 scale-105 shadow-lg z-50' : ''}
        ${className}
      `}
      onClick={handleClick}
    >
      {/* Card Header */}
      <div className="p-4 pb-3">
        <div className="flex items-start justify-between">
          <div className="flex-1 min-w-0">
            <h4 className="text-sm font-semibold text-gray-900 truncate">
              {order.pipelineNumber}
            </h4>
            <p className="text-sm text-gray-600 truncate mt-1">
              {order.accountName}
            </p>
          </div>
          
          {/* Assigned User Avatar */}
          <UserAvatar
            user={order.assignedUser}
            size="sm"
            className="ml-2 flex-shrink-0"
          />
        </div>

        {/* Order Value and Priority */}
        <div className="flex items-center justify-between mt-3">
          <span className={`text-lg font-bold ${
            order.totalValue > 50000 ? 'text-green-600' : 
            order.totalValue > 20000 ? 'text-blue-600' : 'text-gray-900'
          }`}>
            {formatCurrency(order.totalValue)}
          </span>
          
          {order.clientPoNumber && (
            <span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
              PO: {order.clientPoNumber}
            </span>
          )}
        </div>
      </div>

      {/* Progress Indicator */}
      <div className="px-4 pb-3">
        <ProgressIndicator
          percentage={completionPercentage}
          daysInStage={order.daysInStage}
          isOverdue={order.isOverdue}
          className="mb-3"
        />
        
        {/* Stage Duration */}
        <div className="flex items-center justify-between text-xs text-gray-500">
          <span>
            {order.daysInStage} day{order.daysInStage !== 1 ? 's' : ''} in stage
          </span>
          {order.expectedCompletionDate && (
            <span>
              Due: {new Date(order.expectedCompletionDate).toLocaleDateString()}
            </span>
          )}
        </div>
      </div>

      {/* Status Badges */}
      {statusBadges.length > 0 && (
        <div className="px-4 pb-3">
          <StatusBadges badges={statusBadges} />
        </div>
      )}

      {/* Card Footer */}
      <div className="px-4 py-3 bg-gray-50 rounded-b-lg border-t border-gray-100">
        <div className="flex items-center justify-between">
          {/* Last Activity */}
          <div className="flex items-center text-xs text-gray-500">
            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
              {order.lastActivity ? 
                `Updated ${formatRelativeTime(order.lastActivity)}` : 
                'No recent activity'
              }
            </span>
          </div>

          {/* Quick Actions */}
          <div className="flex items-center space-x-1">
            {/* Notes Indicator */}
            {order.hasNotes && (
              <div 
                className="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                title="Has notes"
              >
                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
              </div>
            )}

            {/* Attachments Indicator */}
            {order.hasAttachments && (
              <div 
                className="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                title="Has attachments"
              >
                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
              </div>
            )}

            {/* Timeline Button */}
            <button
              className="p-1 text-gray-400 hover:text-blue-600 transition-colors"
              onClick={(e) => {
                e.stopPropagation();
                onClick(order.id);
              }}
              title="View timeline"
            >
              <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      {/* Drag Handle Indicator */}
      {!isDragging && !sortableIsDragging && (
        <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <div className="text-gray-400">
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
            </svg>
          </div>
        </div>
      )}
    </div>
  );
};

// Utility function to format relative time
const formatRelativeTime = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInMs = now.getTime() - date.getTime();
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
  const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60));
  const diffInMinutes = Math.floor(diffInMs / (1000 * 60));

  if (diffInDays > 0) {
    return `${diffInDays}d ago`;
  } else if (diffInHours > 0) {
    return `${diffInHours}h ago`;
  } else if (diffInMinutes > 0) {
    return `${diffInMinutes}m ago`;
  } else {
    return 'Just now';
  }
};

export default PipelineCard;
