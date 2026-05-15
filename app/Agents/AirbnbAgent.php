<?php

namespace App\Agents;

use App\Tools\ListingDetailsTool;
use App\Tools\ListingSearchTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * AirbnbAgent — The AI-powered travel concierge for Airbnb Arena.
 *
 * Implements the Laravel AI SDK Agent interface with tool-calling support.
 * The agent uses Gemini as its LLM provider and has access to two tools:
 *
 * - ListingSearchTool: Semantic vector search via MongoDB Atlas $vectorSearch
 * - ListingDetailsTool: Fetch full listing details by MongoDB document ID
 *
 * The SDK handles the entire tool-calling loop automatically:
 * 1. Send user message to Gemini with tool definitions
 * 2. If Gemini requests a tool call, execute it
 * 3. Send tool results back to Gemini
 * 4. Repeat until Gemini returns a final text response
 */
#[MaxSteps(10)]
#[Timeout(120)]
class AirbnbAgent implements Agent, HasTools
{
    use Promptable;
    /**
     * The system prompt that defines the agent's personality and rules.
     */

    public function instructions(): string
    {
        return <<<PROMPT
            You are the **Airbnb Arena Host** — an enthusiastic, knowledgeable travel concierge AI powered by MongoDB Atlas Vector Search and Voyage AI embeddings.
                
            Your role:
            - Help users find the perfect Airbnb listing from the sample_airbnb dataset
            - Use the search_listings tool to find properties matching user descriptions
            - Use the get_listing_details tool when users want more info about a specific listing
            - Compare listings when users are deciding between options
            - Provide personalized recommendations based on preferences
                
            Personality:
            - Friendly and enthusiastic about travel 🌍
            - Knowledgeable about different neighborhoods and property types
            - Helpful with practical advice (best for families, couples, budget, luxury, etc.)
            - Use emojis sparingly but effectively
            - Format responses with markdown for readability
                
            Important rules:
            - Always use the search tool to find real listings — never make up properties
            - When showing search results, highlight the key differentiators
            - If the user's query is vague, ask clarifying questions
            - Mention the listing IDs so users can ask for more details
        PROMPT;
    }

    /**
     * The tools available to this agent.
     * The SDK automatically generates the JSON schema for Gemini
     * from each tool's description() and schema() methods.
     */
    public function tools(): iterable
    {
        return [
            app(ListingSearchTool::class),
            new ListingDetailsTool(),
        ];
    }
}
