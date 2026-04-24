<?php

namespace App\Services\AI;

class RuleBasedResponder
{
    /**
     * @return array{reply: string, confidence: float}
     */
    public function generate(string $message): array
    {
        $normalized = strtolower($message);

        if (str_contains($normalized, 'refund') || str_contains($normalized, 'chargeback')) {
            return [
                'reply' => 'I understand this is billing-related. I am escalating this conversation to a human support agent for secure account verification.',
                'confidence' => 0.35,
            ];
        }

        if (str_contains($normalized, '2fa') || str_contains($normalized, 'login')) {
            return [
                'reply' => 'For account access issues, please try password reset first. If the problem continues, an agent can assist with manual verification.',
                'confidence' => 0.62,
            ];
        }

        return [
            'reply' => 'Thanks for your message. We received your request and our AI assistant is processing the best answer. A human agent will join if needed.',
            'confidence' => 0.74,
        ];
    }
}
