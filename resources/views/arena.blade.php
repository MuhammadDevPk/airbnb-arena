<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airbnb Arena - AI Travel Concierge</title>
    
    <!-- Scripts & Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.3); }
        .chat-container::-webkit-scrollbar { width: 6px; }
        .chat-container::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
        
        .message-bubble { max-width: 85%; }
        
        /* Markdown Styling */
        .prose strong { color: #1e293b; font-weight: 700; }
        .prose ul { margin-top: 0.5rem; margin-bottom: 0.5rem; list-style-type: disc; padding-left: 1.25rem; }
        .prose li { margin-bottom: 0.25rem; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out forwards; }
        
        .listing-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-[#F7F7F7] text-slate-900 h-screen flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="glass sticky top-0 z-50 px-6 py-4 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-col items-center text-center">
            <h1 class="text-2xl font-extrabold text-slate-800 flex items-center gap-2">
                <span class="text-3xl">🏠</span> Airbnb Arena
            </h1>
            <p class="text-[10px] text-slate-500 mt-1 uppercase tracking-[0.2em] font-bold">
                AI-powered listing search • MongoDB Atlas Vector Search • Voyage AI • Gemini
            </p>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden relative max-w-[1600px] mx-auto w-full">
        
        <!-- Main Chat Flow -->
        <div class="flex-1 flex flex-col bg-white shadow-2xl relative z-10">
            
            <!-- Messages Container -->
            <div id="chat-window" class="chat-container flex-1 overflow-y-auto p-6 space-y-6 pb-36">
                
                <!-- Welcome Message -->
                <div class="flex justify-start fade-in">
                    <div class="bg-slate-100 text-slate-800 rounded-2xl rounded-tl-none p-5 shadow-sm border border-slate-200/50 message-bubble">
                        <p class="font-bold mb-2 text-indigo-600 flex items-center gap-2 text-lg">
                            Welcome to Airbnb Arena! 👋
                        </p>
                        <div class="prose prose-slate prose-sm text-slate-600 leading-relaxed">
                            I'm your <strong>AI Travel Concierge</strong>. I use semantic vector search to find properties that match exactly what you're looking for, even if you don't use specific keywords.
                            <br><br>
                            Try asking me something like:
                            <ul>
                                <li>"Find me a <strong>cozy apartment in Porto</strong> with a river view"</li>
                                <li>"I need a <strong>family-friendly house</strong> in Portugal with at least 3 beds"</li>
                                <li>"Modern lofts in New York with a high rating"</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Fixed Bottom Input Bar -->
            <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-white via-white to-transparent">
                <form id="chat-form" class="max-w-4xl mx-auto relative flex gap-3 items-center">
                    <div class="relative flex-1 group">
                        <input 
                            type="text" 
                            id="user-input"
                            placeholder="Describe your perfect stay..." 
                            class="w-full pl-6 pr-12 py-4 bg-white border border-slate-200 rounded-2xl shadow-xl group-hover:border-indigo-300 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all text-slate-700"
                            autocomplete="off"
                        >
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <button 
                        type="submit" 
                        id="send-btn"
                        class="h-[58px] px-8 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 active:scale-95 transition-all shadow-lg shadow-indigo-200 flex items-center justify-center disabled:opacity-50 disabled:pointer-events-none"
                    >
                        Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Desktop Listings Panel -->
        <aside id="listings-panel" class="w-[450px] bg-[#F7F7F7] border-l border-slate-200 overflow-y-auto p-6 hidden xl:block">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-800">Top Matches</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Vector Search Results</p>
                </div>
                <span id="results-count" class="text-[10px] font-bold bg-indigo-100 text-indigo-600 px-3 py-1.5 rounded-full uppercase tracking-tighter">0 Results</span>
            </div>

            <div id="listings-container" class="space-y-6">
                <!-- Placeholder State -->
                <div class="flex flex-col items-center justify-center py-32 text-center">
                    <div class="w-20 h-20 bg-white rounded-3xl shadow-sm flex items-center justify-center text-4xl mb-6 grayscale opacity-50">
                        🔭
                    </div>
                    <h3 class="text-slate-400 font-bold text-sm">Waiting for your search...</h3>
                    <p class="text-slate-400 text-[11px] mt-2 max-w-[200px]">Ask the concierge to find properties to see results here.</p>
                </div>
            </div>
        </aside>

    </main>

    <script>
        const chatWindow = document.getElementById('chat-window');
        const chatForm = document.getElementById('chat-form');
        const userInput = document.getElementById('user-input');
        const listingsContainer = document.getElementById('listings-container');
        const resultsCount = document.getElementById('results-count');
        const sendBtn = document.getElementById('send-btn');

        let history = [];

        // Configure Marked
        marked.setOptions({
            breaks: true,
            gfm: true
        });

        function appendMessage(role, content) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'} fade-in mb-6`;
            
            const bubble = document.createElement('div');
            bubble.className = role === 'user' 
                ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none p-5 shadow-lg shadow-indigo-100 message-bubble border border-indigo-500'
                : 'bg-slate-100 text-slate-800 rounded-2xl rounded-tl-none p-5 shadow-sm message-bubble border border-slate-200/50';

            if (role === 'assistant') {
                bubble.innerHTML = `<div class="prose prose-slate prose-sm max-w-none">${marked.parse(content)}</div>`;
            } else {
                bubble.className += ' font-medium';
                bubble.textContent = content;
            }

            wrapper.appendChild(bubble);
            chatWindow.appendChild(wrapper);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        function renderListings(listings) {
            if (!listings || listings.length === 0) return;

            resultsCount.textContent = `${listings.length} Results`;
            listingsContainer.innerHTML = '';

            listings.forEach(listing => {
                const card = document.createElement('div');
                card.className = 'listing-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden cursor-pointer transition-all hover:shadow-xl hover:border-indigo-100 group';
                
                const imageUrl = listing.image_url || 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?q=80&w=500&auto=format&fit=crop';
                
                card.innerHTML = `
                    <div class="relative h-48 overflow-hidden">
                        <img src="${imageUrl}" alt="${listing.name}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute top-4 left-4 bg-white/95 backdrop-blur px-3 py-1.5 rounded-xl text-sm font-black text-slate-800 shadow-xl border border-slate-100">
                            $${listing.price}<span class="text-[10px] font-normal text-slate-500">/night</span>
                        </div>
                        <div class="absolute bottom-4 right-4 bg-indigo-600 px-2 py-1 rounded-lg text-[10px] font-bold text-white shadow-lg uppercase tracking-tighter">
                            ${Math.round(listing.score)}% Match
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center gap-1.5 mb-2">
                            <span class="text-amber-400 text-sm">★</span>
                            <span class="text-xs font-black text-slate-700">${listing.rating || 'N/A'}</span>
                            <span class="text-slate-300 mx-1">•</span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${listing.property_type || 'Stay'}</span>
                        </div>
                        <h3 class="font-extrabold text-slate-800 text-base mb-2 line-clamp-1 group-hover:text-indigo-600 transition-colors">${listing.name}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">${listing.summary || 'Click to view full details and description of this beautiful property.'}</p>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">${listing.location || 'Porto, Portugal'}</span>
                            </div>
                            <span class="text-xs font-bold text-indigo-600 flex items-center gap-1">
                                Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </div>
                `;
                listingsContainer.appendChild(card);
            });
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (!message) return;

            // Update UI
            appendMessage('user', message);
            userInput.value = '';
            userInput.disabled = true;
            sendBtn.disabled = true;

            // Loading state
            const loadingWrapper = document.createElement('div');
            loadingWrapper.className = 'flex justify-start fade-in mb-6';
            loadingWrapper.id = 'loading-bubble';
            loadingWrapper.innerHTML = `
                <div class="bg-slate-100 rounded-2xl rounded-tl-none p-5 flex gap-1.5 border border-slate-200/50 shadow-sm">
                    <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce [animation-duration:0.8s]"></span>
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce [animation-duration:0.8s] [animation-delay:0.2s]"></span>
                    <span class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce [animation-duration:0.8s] [animation-delay:0.4s]"></span>
                </div>
            `;
            chatWindow.appendChild(loadingWrapper);
            chatWindow.scrollTo({ top: chatWindow.scrollHeight, behavior: 'smooth' });

            try {
                const response = await axios.post('/chat', {
                    message: message,
                    history: history
                });

                // Remove loading
                const loader = document.getElementById('loading-bubble');
                if (loader) loader.remove();

                const data = response.data;
                if (data.success) {
                    appendMessage('assistant', data.reply);
                    renderListings(data.listings);
                    
                    // Update History
                    history.push({ role: 'user', content: message });
                    history.push({ role: 'assistant', content: data.reply });
                    
                    // Keep history manageable (last 10 turns)
                    if (history.length > 20) history = history.slice(-20);
                } else {
                    appendMessage('assistant', "I'm sorry, I encountered an error while processing your request.");
                }
            } catch (error) {
                console.error(error);
                const loader = document.getElementById('loading-bubble');
                if (loader) loader.remove();
                appendMessage('assistant', "Something went wrong. Please ensure your API keys (Gemini & Voyage AI) are correctly configured in the `.env` file.");
            } finally {
                userInput.disabled = false;
                sendBtn.disabled = false;
                userInput.focus();
            }
        });
    </script>
</body>
</html>
