
import React, { useState, useEffect } from 'react';

const TimeAgo: React.FC<{ timestamp: string }> = ({ timestamp }) => {
    const [timeAgo, setTimeAgo] = useState('');

    useEffect(() => {
        const calculateTimeAgo = () => {
            const seconds = Math.floor((new Date().getTime() - new Date(timestamp).getTime()) / 1000);
            if (seconds < 60) return setTimeAgo('Just now');
            let interval = seconds / 31536000;
            if (interval > 1) return setTimeAgo(Math.floor(interval) + "y ago");
            interval = seconds / 2592000;
            if (interval > 1) return setTimeAgo(Math.floor(interval) + "mo ago");
            interval = seconds / 86400;
            if (interval > 1) return setTimeAgo(Math.floor(interval) + "d ago");
            interval = seconds / 3600;
            if (interval > 1) return setTimeAgo(Math.floor(interval) + "h ago");
            interval = seconds / 60;
            if (interval > 1) return setTimeAgo(Math.floor(interval) + "m ago");
            setTimeAgo(Math.floor(seconds) + "s ago");
        };

        calculateTimeAgo();
        const timer = setInterval(calculateTimeAgo, 60000);
        return () => clearInterval(timer);
    }, [timestamp]);

    return <span className="text-xs text-[var(--color-text-subtle)]">{timeAgo}</span>;
};

export default TimeAgo;
