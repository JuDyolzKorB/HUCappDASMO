
import React from 'react';
import { Page } from '../types';

interface PagePlaceholderProps {
  pageName: Page;
}

const PagePlaceholder: React.FC<PagePlaceholderProps> = ({ pageName }) => (
  <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-lg h-full flex flex-col items-center justify-center text-center border-2 border-dashed border-[var(--color-border)]">
    <div className="text-6xl text-[var(--color-text-subtle)] mb-4">
      <svg xmlns="http://www.w.org/2000/svg" className="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </div>
    <h2 className="text-2xl font-bold text-[var(--color-text-base)]">{pageName}</h2>
    <p className="mt-2 text-[var(--color-text-muted)]">This page is currently under construction.</p>
    <p className="text-[var(--color-text-muted)]">Functionality will be added in a future update.</p>
  </div>
);

export default PagePlaceholder;