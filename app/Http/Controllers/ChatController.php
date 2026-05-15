<?php

namespace App\Http\Controllers;

use App\Agents\AirbnbAgent;
use App\Tools\ListingSearchTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ChatController — Handles the Airbnb Arena chat interface.
 *
 * Uses the Laravel AI SDK's Agent pattern to connect users to Gemini.
 * The SDK handles tool-calling automatically — when Gemini decides
 * it needs to search or fetch details, the SDK executes the tools
 * and feeds results back to the model.
 *
 * Architecture:
 * 1. User sends a message
 * 2. AirbnbAgent (via SDK) sends it to Gemini with tool definitions
 * 3. Gemini may call tools (search/details) — SDK executes them
 * 4. SDK sends tool results back to Gemini for final response
 * 5. Return the formatted response to the user
 */
class ChatController extends Controller
{
    /**
     * Show the chat UI.
     */

    public function index()
    {
        return view('arena');
    }

    public function chat(Request $request)
    {
        // Allow up to 2 minutes for the agent loop (embedding + vector search + LLM reasoning)
        set_time_limit(120);

        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'array',
        ]);

        $userMessage = $request->input('message');
        $history = $request->input('history', []);

        try {

            // Build the prompt with conversation history for context
            $prompt = $this->buildPrompt($userMessage, $history);

            // Create the agent (via make() for dependency injection) and send the prompt
            $agent = AirbnbAgent::make();
            $response = $agent->prompt($prompt, provider: 'gemini');

            // Collect any structured listing data from the search tool
            $searchTool = app(ListingSearchTool::class);
            $listings = $searchTool->getLastResults();

            return response()->json([
                'reply' => (string) $response,
                'listings' => $listings,
                'success' => true,
            ]);

        } catch (\Exception $e) {

            Log::error('ChatController error', ['error' => $e->getMessage()]);
            return response()->json([
                'reply' => "Sorry, I encountered an error: {$e->getMessage()}",
                'success' => false,
            ], 500);

        }
    }

    private function buildPrompt(string $userMessage, array $history): string
    {

        if (empty($history)) {
            return $userMessage;
        }

        // Include recent conversation history so the agent understands context
        // (e.g., "Tell me more about the second one" refers to previous results)
        $context = collect($history)->map(function ($msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            return "{$role}: {$msg['content']}";
        })->implode("\n\n");

        return "{$context}\n\nUser: {$userMessage}";
    }

}
