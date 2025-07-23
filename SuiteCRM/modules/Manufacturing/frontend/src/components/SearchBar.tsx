/**
 * SearchBar Component
 * Google-like search input with autocomplete and voice search
 */

import React, { forwardRef, useState, useCallback, useEffect } from 'react';

interface SearchBarProps {
    value: string;
    onChange: (value: string) => void;
    onSubmit: (value: string) => void;
    onFocus?: () => void;
    onBlur?: () => void;
    placeholder?: string;
    loading?: boolean;
    className?: string;
    autoFocus?: boolean;
}

const SearchBar = forwardRef<HTMLInputElement, SearchBarProps>(({
    value,
    onChange,
    onSubmit,
    onFocus,
    onBlur,
    placeholder = "Search products...",
    loading = false,
    className = "",
    autoFocus = false
}, ref) => {
    const [focused, setFocused] = useState(false);
    const [voiceSearchAvailable, setVoiceSearchAvailable] = useState(false);
    const [isListening, setIsListening] = useState(false);

    // Check for speech recognition support
    useEffect(() => {
        const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
        setVoiceSearchAvailable(!!SpeechRecognition);
    }, []);

    const handleSubmit = useCallback((e: React.FormEvent) => {
        e.preventDefault();
        if (value.trim()) {
            onSubmit(value.trim());
        }
    }, [value, onSubmit]);

    const handleFocus = useCallback(() => {
        setFocused(true);
        onFocus?.();
    }, [onFocus]);

    const handleBlur = useCallback(() => {
        setFocused(false);
        onBlur?.();
    }, [onBlur]);

    const handleVoiceSearch = useCallback(() => {
        if (!voiceSearchAvailable || isListening) return;

        const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
        const recognition = new SpeechRecognition();

        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-US';

        setIsListening(true);

        recognition.onstart = () => {
            setIsListening(true);
        };

        recognition.onresult = (event: any) => {
            const transcript = event.results[0][0].transcript;
            onChange(transcript);
            setTimeout(() => onSubmit(transcript), 100);
        };

        recognition.onerror = (event: any) => {
            console.error('Speech recognition error:', event.error);
            setIsListening(false);
        };

        recognition.onend = () => {
            setIsListening(false);
        };

        recognition.start();
    }, [voiceSearchAvailable, isListening, onChange, onSubmit]);

    const handleClear = useCallback(() => {
        onChange('');
        (ref as React.RefObject<HTMLInputElement>)?.current?.focus();
    }, [onChange, ref]);

    return (
        <form onSubmit={handleSubmit} className={`relative ${className}`}>
            <div className={`relative flex items-center bg-white border-2 rounded-lg transition-all duration-200 ${
                focused 
                    ? 'border-blue-500 shadow-lg' 
                    : 'border-gray-300 hover:border-gray-400'
            }`}>
                {/* Search Icon */}
                <div className="absolute left-3 text-gray-400">
                    {loading ? (
                        <div className="animate-spin">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                    ) : (
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    )}
                </div>

                {/* Search Input */}
                <input
                    ref={ref}
                    type="text"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    onFocus={handleFocus}
                    onBlur={handleBlur}
                    placeholder={placeholder}
                    autoFocus={autoFocus}
                    className="w-full pl-10 pr-20 py-3 text-lg bg-transparent border-none outline-none placeholder-gray-400"
                    autoComplete="off"
                    spellCheck="false"
                />

                {/* Right Side Actions */}
                <div className="absolute right-2 flex items-center space-x-1">
                    {/* Clear Button */}
                    {value && (
                        <button
                            type="button"
                            onClick={handleClear}
                            className="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors"
                            title="Clear search"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                      d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    )}

                    {/* Voice Search Button */}
                    {voiceSearchAvailable && (
                        <button
                            type="button"
                            onClick={handleVoiceSearch}
                            disabled={isListening}
                            className={`p-2 rounded-full transition-colors ${
                                isListening 
                                    ? 'text-red-500 bg-red-50' 
                                    : 'text-gray-400 hover:text-blue-500 hover:bg-blue-50'
                            }`}
                            title={isListening ? "Listening..." : "Voice search"}
                        >
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/>
                            </svg>
                        </button>
                    )}

                    {/* Search Button */}
                    <button
                        type="submit"
                        disabled={!value.trim() || loading}
                        className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Search"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* Search Shortcuts Hint */}
            {focused && !value && (
                <div className="absolute top-full left-0 right-0 mt-1 p-2 bg-white border border-gray-200 rounded-lg shadow-lg text-sm text-gray-500 z-10">
                    <div className="flex items-center justify-between">
                        <span>Search by product name, SKU, or material</span>
                        <div className="flex items-center space-x-2 text-xs">
                            <kbd className="px-2 py-1 bg-gray-100 rounded">Enter</kbd>
                            <span>to search</span>
                        </div>
                    </div>
                </div>
            )}

            {/* Voice Search Indicator */}
            {isListening && (
                <div className="absolute top-full left-0 right-0 mt-1 p-3 bg-red-50 border border-red-200 rounded-lg shadow-lg z-10">
                    <div className="flex items-center justify-center space-x-2 text-red-600">
                        <div className="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                        <span className="font-medium">Listening... Speak now</span>
                    </div>
                </div>
            )}
        </form>
    );
});

SearchBar.displayName = 'SearchBar';

export default SearchBar;
