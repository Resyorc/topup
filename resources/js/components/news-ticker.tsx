import React from 'react';

interface NewsTickerProps {
    messages: string[];
    speed?: number; // Duration in seconds for one full scroll
    bgColor?: string;
    customClass?: string;
    separator?: string;
}

export default function NewsTicker({ 
    messages, 
    speed = 20, 
    bgColor = 'bg-transparent', 
    customClass = '', 
    separator = '•' 
}: NewsTickerProps) {
    if (!messages || messages.length === 0) return null;

    // Join messages with the separator
    const textContext = messages.join(` ${separator} `) + ` ${separator} `;

    return (
        <div className={`overflow-hidden whitespace-nowrap flex items-center w-full relative ${bgColor} ${customClass}`}>
            <div 
                className="inline-block animate-marquee"
                style={{ animationDuration: `${speed}s` }}
            >
                <span className="mx-4 text-xs font-medium text-gray-200">{textContext}</span>
                <span className="mx-4 text-xs font-medium text-gray-200">{textContext}</span>
                <span className="mx-4 text-xs font-medium text-gray-200">{textContext}</span>
                <span className="mx-4 text-xs font-medium text-gray-200">{textContext}</span>
            </div>
        </div>
    );
}

