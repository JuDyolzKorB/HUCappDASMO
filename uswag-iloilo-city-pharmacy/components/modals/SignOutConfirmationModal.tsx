import React from 'react';
import Modal from './Modal';
import PowerIcon from '../icons/PowerIcon';

interface SignOutConfirmationModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
}

const SignOutConfirmationModal: React.FC<SignOutConfirmationModalProps> = ({ isOpen, onClose, onConfirm }) => {
  const modalId = React.useId();
  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-md" titleId={modalId}>
      <div className="p-8 text-center">
        <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50">
          <PowerIcon className="h-8 w-8 text-red-500 dark:text-red-400" />
        </div>
        <div className="mt-5">
          <h3 className="text-xl leading-6 font-bold text-[var(--color-text-base)]" id={modalId}>
            Sign Out?
          </h3>
          <div className="mt-2">
            <p className="text-sm text-[var(--color-text-muted)]">
              This will end your current session and you will be required to sign in again.
            </p>
          </div>
        </div>
        <div className="mt-8 flex justify-center space-x-4">
          <button type="button" onClick={onClose} className="btn btn-secondary w-full">
            Stay Signed In
          </button>
          <button type="button" onClick={onConfirm} className="btn btn-danger w-full">
            Confirm Sign Out
          </button>
        </div>
      </div>
    </Modal>
  );
};

export default SignOutConfirmationModal;