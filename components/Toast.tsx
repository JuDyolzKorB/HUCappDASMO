import React, { useState, useEffect } from 'react';
import { ToastNotification } from '../types';
import CheckCircleIcon from './icons/CheckCircleIcon';
import XCircleIcon from './icons/XCircleIcon';
import ExclamationTriangleIcon from './icons/ExclamationTriangleIcon';
import InformationCircleIcon from './icons/InformationCircleIcon';

interface ToastProps {
  toast: ToastNotification;
  onDismiss: (id: string) => void;
}

const Toast: React.FC<ToastProps> = ({ toast, onDismiss }) => {
  const [isExiting, setIsExiting] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsExiting(true);
      setTimeout(() => onDismiss(toast.id), 500); // Wait for exit animation
    }, 4000); 

    return () => clearTimeout(timer);
  }, [toast.id, onDismiss]);

  const handleDismiss = () => {
    setIsExiting(true);
    setTimeout(() => onDismiss(toast.id), 500); // Wait for exit animation
  };

  const getIcon = () => {
    const iconProps = { className: `toast-icon toast-${toast.type}` };
    switch(toast.type) {
        case 'success': return <CheckCircleIcon {...iconProps} />;
        case 'danger': return <XCircleIcon {...iconProps} />;
        case 'warning': return <ExclamationTriangleIcon {...iconProps} />;
        case 'info':
        default:
            return <InformationCircleIcon {...iconProps} />;
    }
  };

  return (
    <div className={`toast ${isExiting ? 'exiting' : ''}`} role="alert">
        {getIcon()}
        <div className="toast-content">{toast.message}</div>
        <button onClick={handleDismiss} className="toast-close" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div className={`toast-progress toast-${toast.type}`}></div>
    </div>
  );
};

export default Toast;
