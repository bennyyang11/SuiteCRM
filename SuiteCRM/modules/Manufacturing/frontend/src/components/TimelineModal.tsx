/**
 * Timeline Modal Component
 * Comprehensive order history timeline with events and stage transitions
 */

import React, { useState, useEffect, useMemo } from 'react';
import { TimelineEvent, OrderPipelineItem } from '../types/Pipeline';
import { useTimelineData } from '../hooks/useTimelineData';
import Modal from './Modal';
import UserAvatar from './UserAvatar';
import LoadingSpinner from './LoadingSpinner';

interface TimelineModalProps {
  orderId: string;
  isOpen: boolean;
  onClose: () => void;
  className?: string;
}

const TimelineModal: React.FC<TimelineModalProps> = ({
  orderId,
  isOpen,
  onClose,
  className = '',
}) => {
  const [filterType, setFilterType] = useState<string>('all');
  const [expandedEvents, setExpandedEvents] = useState<Set<string>>(new Set());

  const {
    order,
    timeline,
    loading,
    error,
    refetch,
    addNote,
    exportTimeline
  } = useTimelineData(orderId);

  // Filter timeline events
  const filteredTimeline = useMemo(() => {
    if (!timeline) return [];
    
    if (filterType === 'all') return timeline;
    
    return timeline.filter(event => {
      switch (filterType) {
        case 'stage_changes':
          return event.eventType === 'stage_change';
        case 'communications':
          return ['email_sent', 'call_logged', 'note_added'].includes(event.eventType);
        case 'system':
          return event.automaticTransition;
        default:
          return true;
      }
    });
  }, [timeline, filterType]);

  // Group events by date
  const groupedTimeline = useMemo(() => {
    const groups: Record<string, TimelineEvent[]> = {};
    
    filteredTimeline.forEach(event => {
      const dateKey = new Date(event.timestamp).toDateString();
      if (!groups[dateKey]) {
        groups[dateKey] = [];
      }
      groups[dateKey].push(event);
    });
    
    return Object.entries(groups).sort(([a], [b]) => 
      new Date(b).getTime() - new Date(a).getTime()
    );
  }, [filteredTimeline]);

  // Calculate timeline metrics
  const timelineMetrics = useMemo(() => {
    if (!timeline || !order) return null;
    
    const stageChanges = timeline.filter(e => e.eventType === 'stage_change');
    const totalDuration = Date.now() - new Date(order.dateEntered).getTime();
    const avgStageTime = stageChanges.length > 0 ? totalDuration / stageChanges.length : 0;
    
    return {
      totalEvents: timeline.length,
      stageChanges: stageChanges.length,
      totalDuration: Math.floor(totalDuration / (1000 * 60 * 60 * 24)), // Days
      avgStageTime: Math.floor(avgStageTime / (1000 * 60 * 60 * 24)), // Days
      automatedEvents: timeline.filter(e => e.automaticTransition).length
    };
  }, [timeline, order]);

  // Event type configurations
  const eventTypeConfig = {
    stage_change: {
      icon: '🔄',
      color: 'blue',
      label: 'Stage Change',
      bgColor: 'bg-blue-50 border-blue-200'
    },
    note_added: {
      icon: '📝',
      color: 'gray',
      label: 'Note Added',
      bgColor: 'bg-gray-50 border-gray-200'
    },
    email_sent: {
      icon: '📧',
      color: 'green',
      label: 'Email Sent',
      bgColor: 'bg-green-50 border-green-200'
    },
    call_logged: {
      icon: '📞',
      color: 'purple',
      label: 'Call Logged',
      bgColor: 'bg-purple-50 border-purple-200'
    },
    document_uploaded: {
      icon: '📎',
      color: 'orange',
      label: 'Document Uploaded',
      bgColor: 'bg-orange-50 border-orange-200'
    },
    payment_received: {
      icon: '💰',
      color: 'green',
      label: 'Payment Received',
      bgColor: 'bg-green-50 border-green-200'
    }
  };

  // Toggle event expansion
  const toggleEventExpansion = (eventId: string) => {
    const newExpanded = new Set(expandedEvents);
    if (newExpanded.has(eventId)) {
      newExpanded.delete(eventId);
    } else {
      newExpanded.add(eventId);
    }
    setExpandedEvents(newExpanded);
  };

  // Handle export
  const handleExport = async () => {
    try {
      await exportTimeline('pdf');
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  if (!isOpen) return null;

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Order Timeline - ${order?.pipelineNumber || 'Loading...'}`}
      size="xl"
      className={className}
    >
      {loading ? (
        <div className="flex items-center justify-center py-12">
          <LoadingSpinner size="large" />
        </div>
      ) : error ? (
        <div className="text-center py-12">
          <div className="text-red-600 mb-4">Failed to load timeline data</div>
          <button
            onClick={refetch}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Retry
          </button>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Timeline Header */}
          <div className="bg-gray-50 rounded-lg p-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {/* Order Summary */}
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                  {order?.accountName}
                </h3>
                <div className="space-y-1 text-sm text-gray-600">
                  <div>Order: {order?.pipelineNumber}</div>
                  <div>Value: ${order?.totalValue.toLocaleString()}</div>
                  <div>Stage: {order?.currentStage.replace('_', ' ')}</div>
                  <div>Priority: {order?.priority}</div>
                </div>
              </div>

              {/* Timeline Metrics */}
              {timelineMetrics && (
                <div>
                  <h4 className="font-medium text-gray-900 mb-2">Timeline Metrics</h4>
                  <div className="space-y-1 text-sm text-gray-600">
                    <div>{timelineMetrics.totalEvents} total events</div>
                    <div>{timelineMetrics.stageChanges} stage changes</div>
                    <div>{timelineMetrics.totalDuration} days total</div>
                    <div>{timelineMetrics.avgStageTime} days avg/stage</div>
                    <div>{timelineMetrics.automatedEvents} automated</div>
                  </div>
                </div>
              )}

              {/* Actions */}
              <div>
                <h4 className="font-medium text-gray-900 mb-2">Actions</h4>
                <div className="space-y-2">
                  <button
                    onClick={handleExport}
                    className="w-full px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                  >
                    Export PDF
                  </button>
                  <button
                    onClick={() => {
                      // Add note functionality
                      const note = prompt('Add a note:');
                      if (note) {
                        addNote(note);
                      }
                    }}
                    className="w-full px-3 py-2 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
                  >
                    Add Note
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Timeline Filters */}
          <div className="flex items-center justify-between">
            <div className="flex space-x-2">
              {[
                { key: 'all', label: 'All Events' },
                { key: 'stage_changes', label: 'Stage Changes' },
                { key: 'communications', label: 'Communications' },
                { key: 'system', label: 'System Events' }
              ].map(filter => (
                <button
                  key={filter.key}
                  onClick={() => setFilterType(filter.key)}
                  className={`px-3 py-1 text-sm rounded transition-colors ${
                    filterType === filter.key
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  {filter.label}
                </button>
              ))}
            </div>
            
            <div className="text-sm text-gray-500">
              {filteredTimeline.length} events
            </div>
          </div>

          {/* Timeline Content */}
          <div className="space-y-6 max-h-96 overflow-y-auto">
            {groupedTimeline.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                No timeline events found
              </div>
            ) : (
              groupedTimeline.map(([dateKey, events]) => (
                <div key={dateKey} className="space-y-4">
                  {/* Date Header */}
                  <div className="sticky top-0 bg-white border-b border-gray-200 pb-2 z-10">
                    <h4 className="font-medium text-gray-900">
                      {new Date(dateKey).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                      })}
                    </h4>
                  </div>

                  {/* Events for this date */}
                  <div className="space-y-3 relative">
                    {/* Timeline line */}
                    <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    
                    {events.map((event, index) => {
                      const config = eventTypeConfig[event.eventType as keyof typeof eventTypeConfig] || eventTypeConfig.note_added;
                      const isExpanded = expandedEvents.has(event.id);
                      
                      return (
                        <div key={event.id} className="relative flex items-start space-x-4">
                          {/* Timeline dot */}
                          <div className={`
                            relative z-10 flex items-center justify-center w-12 h-12 rounded-full
                            ${config.bgColor} border-2
                          `}>
                            <span className="text-lg">{config.icon}</span>
                          </div>

                          {/* Event content */}
                          <div className="flex-1 min-w-0">
                            <div
                              className={`
                                p-4 rounded-lg border cursor-pointer transition-all hover:shadow-sm
                                ${config.bgColor}
                              `}
                              onClick={() => toggleEventExpansion(event.id)}
                            >
                              {/* Event header */}
                              <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                  <span className="font-medium text-gray-900">
                                    {config.label}
                                  </span>
                                  {event.automaticTransition && (
                                    <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                      Automated
                                    </span>
                                  )}
                                </div>
                                
                                <div className="flex items-center space-x-2 text-sm text-gray-500">
                                  <span>
                                    {new Date(event.timestamp).toLocaleTimeString('en-US', {
                                      hour: '2-digit',
                                      minute: '2-digit'
                                    })}
                                  </span>
                                  <UserAvatar user={event.user} size="xs" />
                                </div>
                              </div>

                              {/* Event description */}
                              <p className="mt-2 text-gray-700">
                                {event.description}
                              </p>

                              {/* Stage transition details */}
                              {event.eventType === 'stage_change' && event.fromStage && event.toStage && (
                                <div className="mt-3 flex items-center space-x-2 text-sm">
                                  <span className="bg-gray-200 px-2 py-1 rounded">
                                    {event.fromStage.replace('_', ' ')}
                                  </span>
                                  <span>→</span>
                                  <span className="bg-green-200 px-2 py-1 rounded">
                                    {event.toStage.replace('_', ' ')}
                                  </span>
                                  {event.duration && (
                                    <span className="text-gray-500 ml-2">
                                      ({Math.round(event.duration / 24)} days in previous stage)
                                    </span>
                                  )}
                                </div>
                              )}

                              {/* Expanded content */}
                              {isExpanded && event.notes && (
                                <div className="mt-3 p-3 bg-white bg-opacity-50 rounded border">
                                  <h5 className="font-medium text-gray-900 mb-1">Additional Details:</h5>
                                  <p className="text-sm text-gray-700">{event.notes}</p>
                                </div>
                              )}

                              {/* Expand indicator */}
                              <div className="flex justify-center mt-2">
                                <button className="text-gray-400 hover:text-gray-600">
                                  <svg 
                                    className={`w-4 h-4 transform transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                  >
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                  </svg>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Timeline Footer */}
          <div className="border-t border-gray-200 pt-4">
            <div className="flex items-center justify-between text-sm text-gray-500">
              <div>
                Timeline created: {order?.dateEntered && new Date(order.dateEntered).toLocaleDateString()}
              </div>
              <div>
                Last updated: {new Date().toLocaleString()}
              </div>
            </div>
          </div>
        </div>
      )}
    </Modal>
  );
};

export default TimelineModal;
