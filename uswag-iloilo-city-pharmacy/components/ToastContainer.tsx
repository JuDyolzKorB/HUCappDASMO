import React from 'react';
import { ToastNotification } from '../types';
import Toast from './Toast';

interface ToastContainerProps {
    toasts: ToastNotification[];
    onDismiss: (id: string) => void;
}

const ToastContainer: React.FC<ToastContainerProps> = ({ toasts, onDismiss }) => {
    return (
        <div className="toast-container">
            {toasts.map((toast) => (
                <Toast key={toast.id} toast={toast} onDismiss={onDismiss} />
            ))}
        </div>
    );
};

export default ToastContainer;
