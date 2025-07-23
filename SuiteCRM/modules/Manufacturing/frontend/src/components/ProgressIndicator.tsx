/**
 * Progress Indicator Component
 * Visual progress bars and indicators for pipeline stages
 */

import React, { useMemo } from 'react';

interface ProgressIndicatorProps {
  percentage: number;
  daysInStage: number;
  isOverdue?: boolean;
  showDays?: boolean;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'linear' | 'circular' | 'mini';
  className?: string;
}

const ProgressIndicator: React.FC<ProgressIndicatorProps> = ({
  percentage,
  daysInStage,
  isOverdue = false,
  showDays = true,
  size = 'md',
  variant = 'linear',
  className = '',
}) => {
  // Determine color based on progress and status
  const progressColor = useMemo(() => {
    if (isOverdue) {
      return {
        bg: 'bg-red-500',
        text: 'text-red-600',
        border: 'border-red-200',
        light: 'bg-red-100'
      };
    } else if (percentage >= 85) {
      return {
        bg: 'bg-green-500',
        text: 'text-green-600',
        border: 'border-green-200',
        light: 'bg-green-100'
      };
    } else if (percentage >= 60) {
      return {
        bg: 'bg-blue-500',
        text: 'text-blue-600',
        border: 'border-blue-200',
        light: 'bg-blue-100'
      };
    } else if (percentage >= 30) {
      return {
        bg: 'bg-yellow-500',
        text: 'text-yellow-600',
        border: 'border-yellow-200',
        light: 'bg-yellow-100'
      };
    } else {
      return {
        bg: 'bg-gray-500',
        text: 'text-gray-600',
        border: 'border-gray-200',
        light: 'bg-gray-100'
      };
    }
  }, [percentage, isOverdue]);

  // Size configurations
  const sizeConfig = {
    sm: {
      height: 'h-1',
      text: 'text-xs',
      circular: 'w-8 h-8',
      fontSize: 'text-xs'
    },
    md: {
      height: 'h-2',
      text: 'text-sm',
      circular: 'w-12 h-12',
      fontSize: 'text-sm'
    },
    lg: {
      height: 'h-3',
      text: 'text-base',
      circular: 'w-16 h-16',
      fontSize: 'text-lg'
    }
  };

  const config = sizeConfig[size];

  if (variant === 'circular') {
    return (
      <div className={`progress-indicator circular ${className}`}>
        <div className="relative inline-flex items-center justify-center">
          <svg className={`${config.circular} transform -rotate-90`} viewBox="0 0 36 36">
            <path
              className="text-gray-200"
              d="M18 2.0845
                a 15.9155 15.9155 0 0 1 0 31.831
                a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeDasharray="100, 100"
            />
            <path
              className={progressColor.text}
              d="M18 2.0845
                a 15.9155 15.9155 0 0 1 0 31.831
                a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeDasharray={`${percentage}, 100`}
              strokeLinecap="round"
            />
          </svg>
          <div className={`absolute inset-0 flex items-center justify-center ${config.fontSize} font-semibold ${progressColor.text}`}>
            {percentage}%
          </div>
        </div>
        {showDays && (
          <div className={`mt-1 text-center ${config.text} text-gray-500`}>
            {daysInStage}d
          </div>
        )}
      </div>
    );
  }

  if (variant === 'mini') {
    return (
      <div className={`progress-indicator mini ${className}`}>
        <div className="flex items-center space-x-2">
          <div className="flex-1">
            <div className={`${config.height} ${progressColor.light} rounded-full overflow-hidden`}>
              <div 
                className={`${config.height} ${progressColor.bg} transition-all duration-300 ease-out`}
                style={{ width: `${Math.min(percentage, 100)}%` }}
              />
            </div>
          </div>
          <span className={`${config.text} ${progressColor.text} font-medium`}>
            {percentage}%
          </span>
        </div>
      </div>
    );
  }

  // Linear variant (default)
  return (
    <div className={`progress-indicator linear ${className}`}>
      {/* Progress Header */}
      <div className="flex items-center justify-between mb-1">
        <div className="flex items-center space-x-2">
          <span className={`${config.text} font-medium text-gray-700`}>
            Progress
          </span>
          {isOverdue && (
            <span className="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">
              Overdue
            </span>
          )}
        </div>
        <div className="flex items-center space-x-2">
          <span className={`${config.text} font-semibold ${progressColor.text}`}>
            {percentage}%
          </span>
          {showDays && (
            <span className={`${config.text} text-gray-500`}>
              ({daysInStage} day{daysInStage !== 1 ? 's' : ''})
            </span>
          )}
        </div>
      </div>

      {/* Progress Bar */}
      <div className={`relative ${config.height} ${progressColor.light} rounded-full overflow-hidden`}>
        <div 
          className={`${config.height} ${progressColor.bg} transition-all duration-500 ease-out relative`}
          style={{ width: `${Math.min(percentage, 100)}%` }}
        >
          {/* Animated shine effect */}
          <div 
            className="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-shine"
            style={{
              background: 'linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent)',
              transform: 'translateX(-100%)',
              animation: 'shine 2s infinite'
            }}
          />
        </div>

        {/* Progress markers */}
        <div className="absolute inset-0 flex justify-between items-center px-1">
          {[25, 50, 75].map(marker => (
            <div 
              key={marker}
              className="w-0.5 h-full bg-gray-300 opacity-50"
              style={{ marginLeft: `${marker}%` }}
            />
          ))}
        </div>
      </div>

      {/* Stage indicators */}
      <div className="flex justify-between text-xs text-gray-400 mt-1 px-1">
        <span>Start</span>
        <span>25%</span>
        <span>50%</span>
        <span>75%</span>
        <span>Complete</span>
      </div>
    </div>
  );
};

// Stage Progress Component - shows progress through all 7 stages
interface StageProgressProps {
  currentStage: string;
  className?: string;
}

export const StageProgress: React.FC<StageProgressProps> = ({
  currentStage,
  className = '',
}) => {
  const stages = [
    { id: 'quote_requested', name: 'Quote Requested', order: 1 },
    { id: 'quote_prepared', name: 'Quote Prepared', order: 2 },
    { id: 'quote_sent', name: 'Quote Sent', order: 3 },
    { id: 'quote_approved', name: 'Quote Approved', order: 4 },
    { id: 'order_processing', name: 'Order Processing', order: 5 },
    { id: 'ready_to_ship', name: 'Ready to Ship', order: 6 },
    { id: 'invoiced_delivered', name: 'Invoiced & Delivered', order: 7 }
  ];

  const currentStageOrder = stages.find(s => s.id === currentStage)?.order || 1;

  return (
    <div className={`stage-progress ${className}`}>
      <div className="flex items-center justify-between mb-2">
        <span className="text-sm font-medium text-gray-700">Pipeline Progress</span>
        <span className="text-sm text-gray-500">
          Stage {currentStageOrder} of {stages.length}
        </span>
      </div>

      <div className="relative">
        {/* Progress line */}
        <div className="absolute top-4 left-4 right-4 h-0.5 bg-gray-200">
          <div 
            className="h-full bg-blue-500 transition-all duration-500"
            style={{ width: `${((currentStageOrder - 1) / (stages.length - 1)) * 100}%` }}
          />
        </div>

        {/* Stage dots */}
        <div className="relative flex justify-between">
          {stages.map((stage, index) => {
            const isCompleted = stage.order < currentStageOrder;
            const isCurrent = stage.order === currentStageOrder;
            const isPending = stage.order > currentStageOrder;

            return (
              <div 
                key={stage.id}
                className="flex flex-col items-center"
                style={{ minWidth: '60px' }}
              >
                <div 
                  className={`
                    w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold
                    transition-all duration-300 relative z-10
                    ${isCompleted ? 'bg-green-500 text-white' : ''}
                    ${isCurrent ? 'bg-blue-500 text-white ring-4 ring-blue-100' : ''}
                    ${isPending ? 'bg-gray-200 text-gray-500' : ''}
                  `}
                >
                  {isCompleted ? (
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  ) : (
                    stage.order
                  )}
                </div>
                <span className={`
                  mt-2 text-xs text-center leading-tight
                  ${isCurrent ? 'text-blue-600 font-medium' : 'text-gray-500'}
                `}>
                  {stage.name}
                </span>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default ProgressIndicator;

// CSS for animations (to be added to main CSS file)
/*
@keyframes shine {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shine {
  animation: shine 2s infinite;
}
*/
