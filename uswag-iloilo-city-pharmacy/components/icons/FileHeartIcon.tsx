
import React from 'react';

const FileHeartIcon: React.FC<{ className?: string }> = ({ className }) => (
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className={className}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.5h-8.01a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5h8.01a1.5 1.5 0 0 1 1.5 1.5v8.231l-2.231 2.231Z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M15.98 11.48a2.5 2.5 0 1 1-5.01 0c.005-1.385 1.12-2.5 2.5-2.5.176 0 .348.02.515.056" />
    </svg>
);

export default FileHeartIcon;
