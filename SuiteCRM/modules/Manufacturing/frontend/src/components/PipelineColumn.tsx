/**
 * Pipeline Column Component
 * Individual Kanban column representing a pipeline stage
 */

import React, { useMemo } from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { PipelineStage, OrderPipelineItem } from '../types/Pipeline';
import PipelineCard from './PipelineCard';

interface PipelineColumnProps {
  stage: PipelineStage;
  orders: OrderPipelineItem[];
  onOrderClick: (orderId: string) => void;
  isLoading?: boolean;
  className?: string;
}

const PipelineColumn: React.FC<PipelineColumnProps> = ({
  stage,
  orders,
  onOrderClick,
  isLoading = false,
  className = '',
}) => {
  const { setNodeRef, isOver } = useDroppable({
    id: stage.id,
  });

  // Calculate column metrics
  const columnMetrics = useMemo(() => {
    const totalValue = orders.reduce((sum, order) => sum + order.totalValue, 0);
    const urgentCount = orders.filter(order => order.priority === 'urgent').length;
    const overdueCount = orders.filter(order => order.isOverdue).length;
    const avgDaysInStage = orders.length > 0 
      ? orders.reduce((sum, order) => sum + order.daysInStage, 0) / orders.length 
      : 0;

    return {
      totalValue,
      urgentCount,
      overdueCount,
      avgDaysInStage: Math.round(avgDaysInStage * 10) / 10
    };
  }, [orders]);

  // Stage color variations
  const getStageStyles = () => {
    const baseColor = stage.color;
    return {
      header: `bg-gradient-to-r from-${baseColor}-500 to-${baseColor}-600`,
      border: `border-${baseColor}-200`,
      hover: `hover:border-${baseColor}-300`,
      dropZone: isOver ? `border-${baseColor}-400 bg-${baseColor}-50` : ''
    };
  };

  const styles = getStageStyles();

  return (
    <div
      className={`pipeline-column flex-shrink-0 w-80 ${className}`}
      data-stage={stage.id}
    >
      {/* Column Header */}
      <div className={`rounded-t-lg px-4 py-3 text-white ${styles.header}`}>
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <h3 className="font-semibold text-lg">{stage.name}</h3>
            <span className="ml-2 bg-white bg-opacity-20 text-white text-xs px-2 py-1 rounded-full">
              {orders.length}
            </span>
          </div>
          
          {/* Stage Actions */}
          <div className="flex items-center space-x-2">
            {columnMetrics.urgentCount > 0 && (
              <span 
                className="bg-red-500 text-white text-xs px-2 py-1 rounded-full"
                title={`${columnMetrics.urgentCount} urgent orders`}
              >
                {columnMetrics.urgentCount}!
              </span>
            )}
            
            {columnMetrics.overdueCount > 0 && (
              <span 
                className="bg-orange-500 text-white text-xs px-2 py-1 rounded-full"
                title={`${columnMetrics.overdueCount} overdue orders`}
              >
                {columnMetrics.overdueCount}⏰
              </span>
            )}
          </div>
        </div>

        {/* Column Metrics */}
        <div className="mt-2 text-sm text-white text-opacity-90">
          <div className="flex justify-between items-center">
            <span>Total: ${columnMetrics.totalValue.toLocaleString()}</span>
            {columnMetrics.avgDaysInStage > 0 && (
              <span>Avg: {columnMetrics.avgDaysInStage}d</span>
            )}
          </div>
        </div>

        {/* Stage Description */}
        <div className="mt-1 text-xs text-white text-opacity-75">
          {stage.description}
        </div>
      </div>

      {/* Drop Zone */}
      <div
        ref={setNodeRef}
        className={`
          min-h-[400px] bg-gray-50 border-2 border-dashed transition-all duration-200
          ${styles.border} ${styles.hover} ${styles.dropZone}
          ${isOver ? 'border-solid' : ''}
          ${isLoading ? 'opacity-60' : ''}
        `}
      >
        {/* Loading Overlay */}
        {isLoading && (
          <div className="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          </div>
        )}

        {/* Orders List */}
        <SortableContext
          items={orders.map(order => order.id)}
          strategy={verticalListSortingStrategy}
        >
          <div className="p-3 space-y-3">
            {orders.length === 0 ? (
              <div className="text-center py-8 text-gray-400">
                <div className="w-12 h-12 mx-auto mb-3 opacity-50">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path 
                      strokeLinecap="round" 
                      strokeLinejoin="round" 
                      strokeWidth={1} 
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" 
                    />
                  </svg>
                </div>
                <p className="text-sm">No orders in this stage</p>
                <p className="text-xs mt-1">Drag orders here to update their status</p>
              </div>
            ) : (
              orders.map((order) => (
                <PipelineCard
                  key={order.id}
                  order={order}
                  onClick={onOrderClick}
                  className="transform transition-transform hover:scale-105"
                />
              ))
            )}
          </div>
        </SortableContext>

        {/* Drop Zone Indicator */}
        {isOver && (
          <div className="absolute inset-0 border-2 border-blue-400 border-dashed rounded bg-blue-50 bg-opacity-50 flex items-center justify-center">
            <div className="text-blue-600 font-medium">
              Drop here to move to {stage.name}
            </div>
          </div>
        )}
      </div>

      {/* Column Footer with Quick Actions */}
      <div className="bg-white border border-t-0 border-gray-200 rounded-b-lg px-4 py-2">
        <div className="flex items-center justify-between text-xs text-gray-500">
          <div className="flex items-center space-x-3">
            <span>
              {orders.length} order{orders.length !== 1 ? 's' : ''}
            </span>
            {stage.avgDuration && stage.avgDuration > 0 && (
              <span>
                Target: {Math.round(stage.avgDuration / 24)}d
              </span>
            )}
          </div>
          
          {/* Quick Action Buttons */}
          <div className="flex items-center space-x-1">
            <button
              className="p-1 hover:bg-gray-100 rounded transition-colors"
              title="Add new order"
              onClick={() => {
                // Handle add new order
                console.log('Add new order to', stage.name);
              }}
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </button>
            
            <button
              className="p-1 hover:bg-gray-100 rounded transition-colors"
              title="Filter this stage"
              onClick={() => {
                // Handle stage filter
                console.log('Filter stage', stage.name);
              }}
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PipelineColumn;
