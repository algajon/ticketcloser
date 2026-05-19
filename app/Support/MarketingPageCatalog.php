<?php

namespace App\Support;

class MarketingPageCatalog
{
    public static function homeFaqItems(): array
    {
        return [
            [
                'question' => 'What is AI phone answering software for business?',
                'answer' => 'AI phone answering software answers inbound business calls, asks the right follow-up questions, and captures the details your team needs without relying on manual note-taking.',
            ],
            [
                'question' => 'How does tickIt turn a phone call into a ticket?',
                'answer' => 'tickIt captures the caller, issue, urgency, and follow-up context during the call, then creates a structured ticket with the transcript and call record attached.',
            ],
            [
                'question' => 'Can tickIt book meetings or callbacks after a call?',
                'answer' => 'Yes. tickIt can suggest or confirm follow-up booking after the request is logged so the caller does not have to repeat the issue later.',
            ],
            [
                'question' => 'Can I keep my existing business phone number?',
                'answer' => 'Yes. You can provision a new number, forward your current number, or import an existing line depending on your setup.',
            ],
            [
                'question' => 'Does tickIt support multilingual voice assistants?',
                'answer' => 'Yes. tickIt supports multilingual assistants, including German, English, Arabic, Spanish, French, and other major business languages through the Vapi and Azure voice stack.',
            ],
        ];
    }

    public static function docsArticles(): array
    {
        return [
            [
                'id' => 'getting-started',
                'eyebrow' => 'Start here',
                'title' => 'Get from signup to first live call',
                'summary' => 'The quickest path to seeing tickIt work for real.',
                'steps' => [
                    'Create your account and verify your email.',
                    'Choose the type of calls your business gets most often.',
                    'Create your first assistant from the prefilled draft.',
                    'Connect a phone number so callers can reach the assistant.',
                    'Make a test call and review the ticket that appears in the app.',
                ],
            ],
            [
                'id' => 'workspace',
                'eyebrow' => 'Workspace setup',
                'title' => 'Set up your workspace the right way',
                'summary' => 'Your workspace is the home for your assistants, calls, tickets, contacts, and meetings.',
                'steps' => [
                    'Use the workspace name your team recognizes every day.',
                    'Choose the workflow that most closely matches your business, like maintenance, front desk, IT support, or customer support.',
                    'Let tickIt prefill your first assistant so you are not starting from a blank screen.',
                    'Keep one workspace per business or team that needs separate assistants and call history.',
                ],
            ],
            [
                'id' => 'assistant',
                'eyebrow' => 'Assistant setup',
                'title' => 'Create an assistant that sounds natural',
                'summary' => 'Assistants answer the phone, ask the right questions, and turn calls into tickets.',
                'steps' => [
                    'Pick the assistant name your team will recognize inside the app.',
                    'Choose a behavior preset that matches how you want the assistant to sound.',
                    'Set the first line the assistant says so your greeting feels on-brand.',
                    'Use the prompt draft as your starting point, then keep the instructions short, clear, and practical.',
                    'Save and sync after changes so the live phone assistant gets the newest version.',
                ],
            ],
            [
                'id' => 'number',
                'eyebrow' => 'Phone numbers',
                'title' => 'Connect your number and go live',
                'summary' => 'This is how callers actually reach your assistant.',
                'steps' => [
                    'Provision a number inside tickIt, import a number you already own, or forward your existing business line once the assistant is ready.',
                    'Keep one number per assistant when you want separate call flows.',
                    'Use the live status in the app to confirm the line is active before testing.',
                    'Place a real test call so you can hear the greeting and check the ticket flow.',
                ],
            ],
            [
                'id' => 'call-flow',
                'eyebrow' => 'What happens on a call',
                'title' => 'How tickIt handles a phone call',
                'summary' => 'The goal is to make the caller repeat less and give your team a cleaner record.',
                'steps' => [
                    'The assistant answers and greets the caller.',
                    'If the caller is already known, tickIt can recognize them and use saved context.',
                    'The assistant gathers the issue, urgency, and the details needed for follow-up.',
                    'After confirmation, tickIt creates the ticket and saves the call context.',
                    'If the workflow needs a meeting, tickIt can help move that into scheduling.',
                ],
            ],
            [
                'id' => 'after-call',
                'eyebrow' => 'After the call',
                'title' => 'Review tickets, contacts, and meetings',
                'summary' => 'Everything important from the call should be easy to find afterward.',
                'steps' => [
                    'Tickets show what happened, who called, and what needs to happen next.',
                    'Contacts keep repeat callers connected to their call history and past tickets.',
                    'Meetings and suggested follow-up stay attached to the relevant ticket.',
                    'Calls keep the recording and transcript together so your team can review what was said.',
                ],
            ],
            [
                'id' => 'improve',
                'eyebrow' => 'Improve results',
                'title' => 'Make the assistant better over time',
                'summary' => 'The best assistants get tighter with real call feedback.',
                'steps' => [
                    'Listen to a few real calls every week.',
                    'Shorten prompts when the assistant sounds too wordy.',
                    'Add clearer instructions when it misses key details.',
                    'Adjust the opening line and behavior preset if the tone feels off.',
                    'Keep using one test call after every important change.',
                ],
            ],
        ];
    }

    public static function features(): array
    {
        return array_values(self::featureDefinitions());
    }

    public static function feature(?string $slug): ?array
    {
        $definitions = self::featureDefinitions();

        return $definitions[(string) $slug] ?? null;
    }

    public static function industries(): array
    {
        return array_values(self::industryDefinitions());
    }

    public static function industry(?string $slug): ?array
    {
        $definitions = self::industryDefinitions();

        return $definitions[(string) $slug] ?? null;
    }

    protected static function featureDefinitions(): array
    {
        return [
            'ai-phone-answering' => [
                'slug' => 'ai-phone-answering',
                'group' => 'features',
                'label' => 'Feature',
                'nav_label' => 'AI phone answering',
                'card_title' => 'AI phone answering',
                'card_summary' => 'Answer business calls with an assistant that captures the issue, urgency, and callback details instead of dropping callers into voicemail.',
                'meta_title' => 'AI phone answering software for business | tickIt',
                'meta_description' => 'Use AI phone answering software that captures caller details, urgency, and next steps, then turns the call into a ticket your team can work.',
                'hero_title' => 'AI phone answering that captures the details, not just the call.',
                'hero_description' => 'tickIt answers inbound business calls, asks the right follow-up questions, and moves the conversation into a structured ticket instead of a missed-call note.',
                'direct_answer' => 'tickIt is AI phone answering software for businesses that need more than a greeting. It captures the caller, issue, urgency, and next step so the team can act fast after the call ends.',
                'highlights' => ['Inbound call coverage', 'Urgency capture', 'Existing number support', 'After-hours ready'],
                'benefits' => [
                    [
                        'title' => 'Answer every call with a consistent flow',
                        'body' => 'Your assistant greets the caller, asks the next logical question, and keeps the call moving without sounding like a rigid phone tree.',
                    ],
                    [
                        'title' => 'Capture what the team actually needs',
                        'body' => 'Instead of a vague message, tickIt records the issue summary, urgency, property or account context, and the best path for follow-up.',
                    ],
                    [
                        'title' => 'Keep the phone line tied to real operations',
                        'body' => 'The call is useful because it leads into the transcript, ticket, contact record, and any follow-up booking right away.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'What happens while the caller is still on the line',
                        'body' => 'tickIt answers in your chosen style, collects the missing details one question at a time, and confirms the request before it becomes work for the team.',
                        'bullets' => [
                            'Use a custom opening line that matches your brand and chosen language.',
                            'Capture caller details without forcing the team to listen back for basics later.',
                            'Keep the conversation natural while still following the right intake steps.',
                        ],
                    ],
                    [
                        'title' => 'Why operations teams use AI phone answering',
                        'body' => 'The value is not just answering more calls. The value is handing the next person a clean record instead of a half-complete voicemail or scribbled note.',
                        'bullets' => [
                            'Less manual call logging.',
                            'Fewer missed urgency cues.',
                            'Better follow-up because the transcript and summary stay attached.',
                        ],
                    ],
                    [
                        'title' => 'Where this fits best',
                        'body' => 'tickIt is strongest when the business depends on inbound calls that must turn into clear action, such as maintenance, reception, IT support, and customer support.',
                        'bullets' => [
                            'Property management and maintenance request intake.',
                            'Front desk coverage and receptionist overflow.',
                            'Support lines that need a ticket before the callback.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Teams that miss details between the phone call and the help desk.',
                    'Businesses that need structured intake instead of generic voicemail.',
                    'Operations that want AI coverage without losing the human follow-up path.',
                ],
                'faq_items' => [
                    [
                        'question' => 'What is AI phone answering software?',
                        'answer' => 'AI phone answering software answers calls automatically, gathers the key details from the caller, and helps route the next step without relying on a live agent for every first-touch conversation.',
                    ],
                    [
                        'question' => 'Can tickIt answer after hours?',
                        'answer' => 'Yes. tickIt can answer after-hours calls, capture the request, and flag urgent issues so your team starts the next shift with a clean record.',
                    ],
                    [
                        'question' => 'Can I keep my current business number?',
                        'answer' => 'Yes. You can forward your current number, import an existing number, or provision a new line inside tickIt depending on your setup.',
                    ],
                    [
                        'question' => 'Does this replace my team?',
                        'answer' => 'No. tickIt is strongest when it handles intake and documentation so your team can spend more time resolving the issue or managing the customer relationship.',
                    ],
                ],
                'related_features' => ['call-to-ticket', 'call-transcripts', 'meeting-booking'],
                'related_industries' => ['property-management', 'reception-and-front-desk', 'service-businesses'],
            ],
            'call-to-ticket' => [
                'slug' => 'call-to-ticket',
                'group' => 'features',
                'label' => 'Feature',
                'nav_label' => 'Call to ticket',
                'card_title' => 'Automatic call-to-ticket workflow',
                'card_summary' => 'Turn inbound phone calls into structured tickets with the caller, issue summary, urgency, transcript, and next step already attached.',
                'meta_title' => 'Automatic call to ticket software | tickIt',
                'meta_description' => 'Turn inbound calls into tickets automatically with summaries, transcripts, caller details, and follow-up context in one record.',
                'hero_title' => 'Turn business phone calls into ready-to-work tickets automatically.',
                'hero_description' => 'tickIt creates the ticket while the call context is still fresh, so your team starts with the request, transcript, contact record, and urgency already organized.',
                'direct_answer' => 'tickIt is call-to-ticket software that converts inbound phone conversations into structured tickets, not loose notes. The caller, issue, urgency, transcript, and next action stay together in one record.',
                'highlights' => ['Structured ticket creation', 'Transcript attached', 'Contact linking', 'Duplicate protection'],
                'benefits' => [
                    [
                        'title' => 'Stop retyping call notes into another system',
                        'body' => 'The ticket is created from the call flow itself, which removes the extra handoff step that usually loses details or delays follow-up.',
                    ],
                    [
                        'title' => 'Keep each call tied to the right contact',
                        'body' => 'tickIt links the caller to their ticket history so repeat calls are easier to understand and faster to handle.',
                    ],
                    [
                        'title' => 'Give the next person enough context to act',
                        'body' => 'A clear ticket title, summary, priority, transcript, and recording make the handoff more useful than a short message slip.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'What the ticket includes',
                        'body' => 'tickIt focuses on the details teams usually scramble to collect after the call, like who called, what happened, how urgent it is, and what should happen next.',
                        'bullets' => [
                            'Ticket title and concise issue summary.',
                            'Caller details and linked contact record.',
                            'Transcript, recording, and suggested follow-up.',
                        ],
                    ],
                    [
                        'title' => 'How it improves phone operations',
                        'body' => 'Manual call logging creates lag and inconsistency. An automatic call-to-ticket workflow helps teams respond from a cleaner starting point.',
                        'bullets' => [
                            'Faster triage after each call.',
                            'Less duplicate data entry.',
                            'Cleaner queue management for busy teams.',
                        ],
                    ],
                    [
                        'title' => 'Who this is built for',
                        'body' => 'Businesses with repeat inbound requests get the most value because the ticket becomes the source of truth for the follow-up conversation.',
                        'bullets' => [
                            'Property managers tracking maintenance requests.',
                            'Reception teams turning messages into assignments.',
                            'Support teams that want a ticket before any callback.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Teams that currently log call notes by hand.',
                    'Businesses that lose information between the phone and the ticket queue.',
                    'Operations that need a transcript and summary in the same place.',
                ],
                'faq_items' => [
                    [
                        'question' => 'How does call-to-ticket software work?',
                        'answer' => 'Call-to-ticket software captures the important details during the conversation, then uses that context to create a structured ticket with the caller, summary, and follow-up path already attached.',
                    ],
                    [
                        'question' => 'Does tickIt store the transcript with the ticket?',
                        'answer' => 'Yes. The transcript and call record stay attached to the ticket so the team can review exactly what the caller said.',
                    ],
                    [
                        'question' => 'Can tickIt prevent duplicate tickets from the same call?',
                        'answer' => 'Yes. tickIt includes duplicate protection on repeated create-case flows tied to the same external call context.',
                    ],
                    [
                        'question' => 'Is this useful for small support teams?',
                        'answer' => 'Yes. Small teams often benefit the most because every lost detail or repeated callback costs time they do not have.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'call-transcripts', 'meeting-booking'],
                'related_industries' => ['property-management', 'it-support', 'customer-support'],
            ],
            'meeting-booking' => [
                'slug' => 'meeting-booking',
                'group' => 'features',
                'label' => 'Feature',
                'nav_label' => 'Meeting booking',
                'card_title' => 'Meeting and callback booking',
                'card_summary' => 'Help callers move straight into a booked callback or meeting after the request is logged, with the booking tied back to the ticket.',
                'meta_title' => 'AI meeting booking from inbound calls | tickIt',
                'meta_description' => 'Book callbacks and meetings after inbound business calls without losing the ticket, transcript, or caller context.',
                'hero_title' => 'Book the next step after the call without losing the ticket context.',
                'hero_description' => 'tickIt helps teams move from intake to scheduling, whether that means a callback, meeting, or maintenance follow-up that should stay attached to the original request.',
                'direct_answer' => 'tickIt handles meeting and callback booking after the request is logged, which means the schedule stays connected to the ticket instead of becoming a separate thread with missing context.',
                'highlights' => ['Suggested events', 'Google Calendar support', 'Ticket-first booking', 'Fallback-safe'],
                'benefits' => [
                    [
                        'title' => 'Book after the request is captured',
                        'body' => 'The assistant can keep the intake clean first, then move into scheduling so the team never loses the reason for the call.',
                    ],
                    [
                        'title' => 'Keep meetings tied to the right case',
                        'body' => 'Suggested or confirmed meetings stay attached to the related ticket, which makes the follow-up easier to understand later.',
                    ],
                    [
                        'title' => 'Fall back safely when booking is not available',
                        'body' => 'If a live calendar booking cannot be confirmed, tickIt can still save the follow-up request instead of pretending it is booked.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'How booking works in practice',
                        'body' => 'tickIt creates or suggests the follow-up event only after the original request exists, which keeps the schedule tied to the case and prevents loose calendar events.',
                        'bullets' => [
                            'Log the call and ticket first.',
                            'Suggest or confirm the follow-up slot next.',
                            'Store the result on the same case record.',
                        ],
                    ],
                    [
                        'title' => 'Where it helps most',
                        'body' => 'Booking matters when callers expect a next step right away, such as a maintenance visit, a support callback, or a front-desk follow-up that should happen on a specific date.',
                        'bullets' => [
                            'Maintenance visit windows.',
                            'Support callbacks that need confirmation.',
                            'Front-desk calls that should become scheduled follow-up.',
                        ],
                    ],
                    [
                        'title' => 'Why ticket-first booking matters',
                        'body' => 'Separating scheduling from intake causes confusion because the team sees a meeting without the phone-call context that created it. ticket-first booking keeps those two moments connected.',
                        'bullets' => [
                            'Clearer handoff for the person handling the follow-up.',
                            'Fewer manual notes copied into calendar descriptions.',
                            'Better audit trail for what was promised to the caller.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Teams that regularly promise callbacks or visits after the first call.',
                    'Operations that already use Google Calendar for follow-up.',
                    'Businesses that want booking connected to the original ticket.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Can tickIt book a meeting during the call?',
                        'answer' => 'Yes. tickIt can help confirm a meeting or callback during the call when the calendar path is available and the ticket has already been created.',
                    ],
                    [
                        'question' => 'What happens if the calendar booking fails?',
                        'answer' => 'tickIt can fall back to a pending suggested follow-up instead of marking the meeting as confirmed when the calendar provider rejects it.',
                    ],
                    [
                        'question' => 'Does the meeting stay linked to the ticket?',
                        'answer' => 'Yes. The follow-up stays attached to the related support case so the team can see why it was booked.',
                    ],
                    [
                        'question' => 'Is this only for sales demos?',
                        'answer' => 'No. It is useful anywhere a caller needs a next step, including maintenance visits, front-desk follow-up, and support callbacks.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'call-to-ticket', 'call-transcripts'],
                'related_industries' => ['property-management', 'reception-and-front-desk', 'customer-support'],
            ],
            'call-transcripts' => [
                'slug' => 'call-transcripts',
                'group' => 'features',
                'label' => 'Feature',
                'nav_label' => 'Call transcripts',
                'card_title' => 'Call transcripts and recordings',
                'card_summary' => 'Keep transcripts and recordings attached to each ticket so your team can review what was said without digging through another tool.',
                'meta_title' => 'Call transcript software for support and operations teams | tickIt',
                'meta_description' => 'Keep call transcripts, recordings, and ticket context together so teams can review what was said and respond with fewer mistakes.',
                'hero_title' => 'Keep the transcript, recording, and ticket in one place.',
                'hero_description' => 'tickIt makes call review useful by attaching the transcript and recording to the exact ticket and contact record they belong to.',
                'direct_answer' => 'tickIt stores call transcripts and recordings alongside the support case, which gives the next person real context instead of a short summary that hides what the caller actually said.',
                'highlights' => ['Transcript storage', 'Recording review', 'Language labels', 'Case context'],
                'benefits' => [
                    [
                        'title' => 'Review what the caller actually said',
                        'body' => 'A transcript helps the team resolve uncertainty without replaying the entire call from memory or relying on a thin summary.',
                    ],
                    [
                        'title' => 'Use transcripts for coaching and prompt tuning',
                        'body' => 'Real call transcripts show where the assistant is handling the intake well and where the script needs tightening.',
                    ],
                    [
                        'title' => 'Keep language and speech context visible',
                        'body' => 'tickIt can store detected language and speech labels so multilingual call history is easier to sort and review.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'Why transcripts matter after the call',
                        'body' => 'The transcript becomes the reference point for callbacks, escalations, and quality review. That matters most when the request is urgent or the caller has already called before.',
                        'bullets' => [
                            'Better callbacks because the next person reads before they dial.',
                            'Cleaner escalation notes for managers or specialists.',
                            'Fewer misunderstandings about names, addresses, or issue details.',
                        ],
                    ],
                    [
                        'title' => 'Useful for multilingual operations',
                        'body' => 'When teams serve callers in more than one language, stored transcript language and assistant language labels make review easier and reduce confusion later.',
                        'bullets' => [
                            'See what language the assistant was configured to use.',
                            'Store detected transcript language from the call sync.',
                            'Keep the ticket aligned with the actual conversation history.',
                        ],
                    ],
                    [
                        'title' => 'Where teams use this most',
                        'body' => 'Transcripts are especially useful for support queues, property teams, and service businesses where missed details can cause a second call, a wasted visit, or an unhappy customer.',
                        'bullets' => [
                            'Support callbacks with technical detail.',
                            'Maintenance calls with access notes or unit details.',
                            'Service businesses handling repeat callers.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Teams that need an audit trail for what was promised.',
                    'Operations that serve callers in more than one language.',
                    'Managers improving assistant prompts from real call history.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Does tickIt save the call transcript automatically?',
                        'answer' => 'Yes. tickIt stores the transcript and keeps it attached to the related call and ticket records when the call data is available.',
                    ],
                    [
                        'question' => 'Can my team review recordings too?',
                        'answer' => 'Yes. Recordings and transcripts stay together so the team can review the exact call when needed.',
                    ],
                    [
                        'question' => 'Can transcripts show what language was used?',
                        'answer' => 'Yes. tickIt can store configured assistant language and detected transcript language labels to make multilingual call history easier to understand.',
                    ],
                    [
                        'question' => 'Why not just keep a short summary?',
                        'answer' => 'Summaries are useful, but transcripts help resolve ambiguity, confirm details, and improve the assistant over time because the raw conversation is still available.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'call-to-ticket', 'multilingual-voice-assistants'],
                'related_industries' => ['it-support', 'customer-support', 'property-management'],
            ],
            'multilingual-voice-assistants' => [
                'slug' => 'multilingual-voice-assistants',
                'group' => 'features',
                'label' => 'Feature',
                'nav_label' => 'Multilingual assistants',
                'card_title' => 'Multilingual AI voice assistants',
                'card_summary' => 'Run assistants in major business languages, including German, English, Arabic, Spanish, French, Portuguese, and more through Vapi and Azure voices.',
                'meta_title' => 'Multilingual AI voice assistants with German and Arabic support | tickIt',
                'meta_description' => 'Use multilingual AI voice assistants with German, English, Arabic, Spanish, French, Portuguese, and other major languages across Vapi and Azure voices.',
                'hero_title' => 'Run multilingual AI voice assistants across the languages your callers use.',
                'hero_description' => 'tickIt supports business-ready assistant languages through the Vapi and Azure voice stack, including German, English, Arabic, Spanish, French, Portuguese, Japanese, Korean, Hindi, Bengali, Urdu, Mandarin, Indonesian, and more.',
                'direct_answer' => 'tickIt supports multilingual AI voice assistants for businesses that serve callers in more than one language. Assistants can speak the chosen language, localize the prompt and opening line, and store language labels with the call record.',
                'highlights' => ['German support', 'Localized prompts', 'Azure voices', 'Language-aware call history'],
                'benefits' => [
                    [
                        'title' => 'Match the assistant language to the business line',
                        'body' => 'Set the assistant to the language your callers expect, whether that is German, English, Arabic, or another supported language.',
                    ],
                    [
                        'title' => 'Keep scripts localized instead of half-translated',
                        'body' => 'tickIt can translate or preserve the assistant prompt and opening line so the live call experience stays aligned with the chosen language.',
                    ],
                    [
                        'title' => 'Review multilingual calls with clearer labels',
                        'body' => 'Stored language labels help teams understand which assistant language was configured and what language the transcript appears to use.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'Languages that matter for global call coverage',
                        'body' => 'tickIt supports the major business languages teams commonly need, including German for DACH coverage and Arabic for regional service teams, alongside English, Spanish, French, Portuguese, and several high-volume global languages.',
                        'bullets' => [
                            'German voice support through Azure voices in the Vapi flow.',
                            'Arabic and English handling for mixed-language service teams.',
                            'Support for additional global languages when your operation expands.',
                        ],
                    ],
                    [
                        'title' => 'What gets localized',
                        'body' => 'The assistant experience is more useful when the live greeting and prompt match the chosen language instead of leaving only part of the interaction translated.',
                        'bullets' => [
                            'Opening line and greeting.',
                            'Assistant prompt and scripted behavior.',
                            'Known-caller language handling during live runtime overrides.',
                        ],
                    ],
                    [
                        'title' => 'Why this matters operationally',
                        'body' => 'Language support is not just a brand detail. It reduces confusion on inbound calls and helps the ticket reflect the real conversation, especially in multilingual teams.',
                        'bullets' => [
                            'Better caller experience on first contact.',
                            'Fewer misunderstandings on urgent requests.',
                            'Cleaner review of transcripts and follow-up notes later.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Teams serving German-speaking, Arabic-speaking, or multilingual callers.',
                    'Businesses that want the assistant prompt and greeting localized automatically.',
                    'Operations that need language-aware transcripts and ticket history.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Does tickIt support German-speaking AI voice assistants?',
                        'answer' => 'Yes. tickIt supports German through the Vapi and Azure voice stack, including German voices that can be used for live assistant calls.',
                    ],
                    [
                        'question' => 'Can the prompt and opening line be translated too?',
                        'answer' => 'Yes. tickIt can localize the assistant prompt and opening line into the chosen assistant language, or leave them unchanged when they are already written in that language.',
                    ],
                    [
                        'question' => 'Which languages are supported?',
                        'answer' => 'tickIt supports major world languages used in business call coverage, including English, German, Arabic, Spanish, French, Portuguese, Mandarin, Japanese, Korean, Hindi, Bengali, Urdu, Indonesian, and Russian.',
                    ],
                    [
                        'question' => 'Can I import a German or US phone number for the assistant?',
                        'answer' => 'Yes. tickIt supports clearer existing-number flows so teams can import or forward numbers such as German +49 or US +1 business lines, depending on their carrier setup.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'call-transcripts', 'meeting-booking'],
                'related_industries' => ['reception-and-front-desk', 'customer-support', 'service-businesses'],
            ],
        ];
    }

    protected static function industryDefinitions(): array
    {
        return [
            'property-management' => [
                'slug' => 'property-management',
                'group' => 'industries',
                'label' => 'Industry',
                'nav_label' => 'Property management',
                'card_title' => 'For property management',
                'card_summary' => 'Capture maintenance calls, resident issues, unit details, and follow-up windows without losing urgency or access context.',
                'meta_title' => 'AI phone answering for property management | tickIt',
                'meta_description' => 'Use AI phone answering for property management to capture maintenance requests, unit details, urgency, access notes, and follow-up booking.',
                'hero_title' => 'AI phone answering for property management and maintenance request intake.',
                'hero_description' => 'tickIt helps property teams capture resident calls, maintenance issues, unit details, urgency, access notes, and visit windows without losing information between the phone line and the work order.',
                'direct_answer' => 'tickIt is a strong fit for property management teams because it turns maintenance and resident calls into structured requests with the building, unit, urgency, and access details already attached.',
                'highlights' => ['Resident call intake', 'Urgency capture', 'Unit and access details', 'Maintenance follow-up'],
                'benefits' => [
                    [
                        'title' => 'Capture building and unit details on the first call',
                        'body' => 'The assistant can collect the property name, unit number, issue summary, and access notes before the request ever reaches the maintenance queue.',
                    ],
                    [
                        'title' => 'Separate urgent issues from routine issues faster',
                        'body' => 'Water leaks, no heat, lockouts, and safety risks can be flagged early so the team knows what needs immediate attention.',
                    ],
                    [
                        'title' => 'Keep the resident request and follow-up together',
                        'body' => 'Tickets, transcripts, and maintenance booking context stay attached to the same case instead of spreading across calls, emails, and notes.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'Why property teams search for this',
                        'body' => 'Property managers often need coverage when the office is busy, after hours, or handling repeat maintenance volume. The pain is not just missed calls. The pain is lost detail between the call and the request.',
                        'bullets' => [
                            'Reduce voicemail dependence for maintenance requests.',
                            'Collect access notes and preferred visit windows right away.',
                            'Give the maintenance team a cleaner starting point.',
                        ],
                    ],
                    [
                        'title' => 'What tickIt can capture for maintenance calls',
                        'body' => 'The assistant flow can ask for the exact pieces that help the team dispatch or follow up with fewer back-and-forth calls.',
                        'bullets' => [
                            'Caller name and callback number.',
                            'Property, building, and unit details.',
                            'Issue summary, urgency, and access instructions.',
                        ],
                    ],
                    [
                        'title' => 'How follow-up stays organized',
                        'body' => 'After the call, the support case becomes the record your team works from, with transcripts, recordings, and any follow-up scheduling still attached.',
                        'bullets' => [
                            'Review the call without asking the resident to repeat themselves.',
                            'Update status from intake through resolution.',
                            'Book a maintenance callback or visit when the workflow needs it.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Property teams with recurring maintenance intake.',
                    'Managers who need clearer triage for urgent resident issues.',
                    'Operations that want fewer missed details between the phone line and the maintenance queue.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Is tickIt good for maintenance request intake?',
                        'answer' => 'Yes. tickIt is well-suited for maintenance request intake because it can capture the unit, issue, urgency, and access details that property teams need to act.',
                    ],
                    [
                        'question' => 'Can it flag urgent property issues?',
                        'answer' => 'Yes. Flows can capture urgency and help highlight issues like active leaks, lockouts, or no-heat calls before the team reviews the queue.',
                    ],
                    [
                        'question' => 'Can it help with follow-up scheduling?',
                        'answer' => 'Yes. tickIt can help save or confirm maintenance-related follow-up after the request is logged.',
                    ],
                    [
                        'question' => 'Does the transcript stay attached to the request?',
                        'answer' => 'Yes. The transcript and call record stay attached to the support case so the next teammate has full context.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'call-to-ticket', 'meeting-booking'],
                'related_industries' => ['service-businesses', 'customer-support'],
            ],
            'reception-and-front-desk' => [
                'slug' => 'reception-and-front-desk',
                'group' => 'industries',
                'label' => 'Industry',
                'nav_label' => 'Reception and front desk',
                'card_title' => 'For reception and front desk',
                'card_summary' => 'Cover inbound calls, capture message details, and route the next step without turning the front desk into a manual note-taking bottleneck.',
                'meta_title' => 'AI receptionist software for front desk teams | tickIt',
                'meta_description' => 'Use AI receptionist software to answer front desk calls, take cleaner messages, create tickets, and book follow-up when needed.',
                'hero_title' => 'AI receptionist software for front desk and inbound call coverage.',
                'hero_description' => 'tickIt helps front desk teams answer more calls, take clearer messages, and turn those calls into structured follow-up instead of disconnected sticky notes.',
                'direct_answer' => 'tickIt works like an AI receptionist for businesses that want cleaner inbound call coverage, message capture, and follow-up routing without making the front desk repeat manual logging work all day.',
                'highlights' => ['Message capture', 'Overflow coverage', 'Callback booking', 'Multilingual options'],
                'benefits' => [
                    [
                        'title' => 'Answer overflow without losing tone',
                        'body' => 'The assistant can greet callers in a polished way, collect the reason for the call, and prepare a cleaner handoff for the front desk or office team.',
                    ],
                    [
                        'title' => 'Turn messages into trackable work',
                        'body' => 'Instead of taking a message that disappears into email or paper, tickIt creates a ticket or record the team can review and assign.',
                    ],
                    [
                        'title' => 'Handle multilingual caller expectations better',
                        'body' => 'When front desk coverage spans languages, the assistant can match the selected voice and greeting more closely to caller expectations.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'What an AI receptionist should actually improve',
                        'body' => 'The real value is not only answering more calls. It is giving the office team a better starting point when they follow up.',
                        'bullets' => [
                            'Cleaner reason-for-call capture.',
                            'Better callback details.',
                            'Less scrambling to remember what the caller needed.',
                        ],
                    ],
                    [
                        'title' => 'Where it helps front desk teams most',
                        'body' => 'This fits businesses that need coverage during busy periods, after hours, lunch breaks, or multi-location overflow without making the experience feel impersonal.',
                        'bullets' => [
                            'Busy reception periods.',
                            'Message-taking outside staffed hours.',
                            'Overflow handling for multi-location teams.',
                        ],
                    ],
                    [
                        'title' => 'How tickIt keeps the follow-up organized',
                        'body' => 'The message becomes a ticket, contact update, or scheduled follow-up that the team can find later, rather than a one-off note that lives in someone inbox.',
                        'bullets' => [
                            'Track messages in one workspace.',
                            'Review the original transcript when details are unclear.',
                            'Book the next step when the caller needs a confirmed callback.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Front desk teams handling more calls than they can answer live.',
                    'Businesses that want better message quality and follow-up tracking.',
                    'Teams that need receptionist coverage in more than one language.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Is tickIt an AI receptionist?',
                        'answer' => 'Yes. tickIt can act like an AI receptionist for inbound business calls by greeting callers, capturing the reason for the call, and handing off the follow-up in a structured way.',
                    ],
                    [
                        'question' => 'Can it take messages after hours?',
                        'answer' => 'Yes. tickIt can answer after hours, collect the message details, and save them for the team to review later.',
                    ],
                    [
                        'question' => 'Can it book a callback?',
                        'answer' => 'Yes. When the workflow needs it, tickIt can help save or confirm a callback or meeting after the intake is logged.',
                    ],
                    [
                        'question' => 'Can we keep our current office number?',
                        'answer' => 'Yes. Teams can provision a new line, forward their current number, or import an existing number based on the carrier setup.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'meeting-booking', 'multilingual-voice-assistants'],
                'related_industries' => ['customer-support', 'service-businesses'],
            ],
            'it-support' => [
                'slug' => 'it-support',
                'group' => 'industries',
                'label' => 'Industry',
                'nav_label' => 'IT support',
                'card_title' => 'For IT support',
                'card_summary' => 'Capture support calls, system details, outage urgency, and callback context so the help desk starts with a cleaner ticket.',
                'meta_title' => 'AI phone answering for IT support teams | tickIt',
                'meta_description' => 'Use AI phone answering for IT support to capture issue summaries, affected systems, urgency, transcripts, and callback details.',
                'hero_title' => 'AI phone answering for IT support lines that need a ticket before the callback.',
                'hero_description' => 'tickIt helps IT support teams capture inbound issue details, identify affected systems, and create a clean ticket with transcript and urgency context attached.',
                'direct_answer' => 'tickIt works well for IT support because it captures the issue summary, affected system, urgency, and callback path before the support team returns the call or starts troubleshooting.',
                'highlights' => ['Issue intake', 'Affected system capture', 'Urgency context', 'Transcript review'],
                'benefits' => [
                    [
                        'title' => 'Start with a clearer support ticket',
                        'body' => 'Instead of a voicemail that says the computer is broken, the team gets a ticket with the caller, issue summary, affected system, and urgency already laid out.',
                    ],
                    [
                        'title' => 'Improve callbacks for busy help desks',
                        'body' => 'Support teams can read before they call back, which helps them ask smarter next questions and move faster.',
                    ],
                    [
                        'title' => 'Keep transcript and ticket in one place',
                        'body' => 'That matters when the issue is technical, the user is frustrated, or the ticket needs to be escalated to another teammate.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'What it can capture for a support call',
                        'body' => 'The assistant can gather the exact details that help a support team triage the issue before human follow-up begins.',
                        'bullets' => [
                            'Caller and callback details.',
                            'Issue summary and affected system.',
                            'Urgency or outage-level impact.',
                        ],
                    ],
                    [
                        'title' => 'Why this matters for IT operations',
                        'body' => 'Support teams waste time when they start with no context. A better first ticket makes callbacks faster and escalations cleaner.',
                        'bullets' => [
                            'Less repeated questioning for the user.',
                            'Faster prioritization of true blockers or outages.',
                            'Cleaner handoff between tiers or teammates.',
                        ],
                    ],
                    [
                        'title' => 'How the workflow stays connected',
                        'body' => 'The transcript, ticket, caller history, and any scheduled follow-up stay tied together instead of splitting across separate systems.',
                        'bullets' => [
                            'Review the call before troubleshooting.',
                            'Use transcript history for repeat callers.',
                            'Keep callback timing attached to the original case.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'IT support teams fielding inbound user issues by phone.',
                    'Help desks that need clearer tickets before a callback.',
                    'Support operations where outages and blockers need fast triage.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Can tickIt work for IT support?',
                        'answer' => 'Yes. tickIt can capture support-call details and create a structured ticket that includes the issue summary, affected system, urgency, and transcript context.',
                    ],
                    [
                        'question' => 'Can it capture outage-level urgency?',
                        'answer' => 'Yes. Intake flows can capture urgency or blocker signals so the team can spot more serious issues faster.',
                    ],
                    [
                        'question' => 'Is the transcript useful for support teams?',
                        'answer' => 'Yes. Support teams can review the transcript before they call back or escalate the case, which reduces repeated questioning and missed detail.',
                    ],
                    [
                        'question' => 'Can it book callbacks?',
                        'answer' => 'Yes. When the workflow needs it, tickIt can help save or confirm a callback after the ticket exists.',
                    ],
                ],
                'related_features' => ['call-to-ticket', 'call-transcripts', 'meeting-booking'],
                'related_industries' => ['customer-support', 'service-businesses'],
            ],
            'customer-support' => [
                'slug' => 'customer-support',
                'group' => 'industries',
                'label' => 'Industry',
                'nav_label' => 'Customer support',
                'card_title' => 'For customer support',
                'card_summary' => 'Handle support calls, capture issue categories, and keep caller context tied to the ticket, transcript, and follow-up path.',
                'meta_title' => 'AI phone answering for customer support teams | tickIt',
                'meta_description' => 'Use AI phone answering for customer support to capture issue categories, caller details, transcripts, and follow-up context in one workflow.',
                'hero_title' => 'AI phone answering for customer support teams that need cleaner intake.',
                'hero_description' => 'tickIt helps support teams capture customer calls, create structured tickets, store transcripts, and keep the callback path tied to the original issue.',
                'direct_answer' => 'tickIt is useful for customer support teams because it turns phone conversations into structured tickets with category, urgency, transcript, and caller context already in place.',
                'highlights' => ['Support call intake', 'Category capture', 'Transcript review', 'Callback tracking'],
                'benefits' => [
                    [
                        'title' => 'Keep support call intake consistent',
                        'body' => 'The assistant can capture the same core support details on every call, which makes the queue easier to review and prioritize.',
                    ],
                    [
                        'title' => 'Reduce repeated questions for repeat callers',
                        'body' => 'Caller history and linked contacts help the next teammate see what happened before the customer has to explain it again.',
                    ],
                    [
                        'title' => 'Improve follow-up quality',
                        'body' => 'Tickets, transcripts, and follow-up requests stay in one place so support teams can respond from a fuller picture.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'What better support intake looks like',
                        'body' => 'Good support intake does not stop at taking a message. It creates a ticket the team can pick up without losing time or asking the customer to start over.',
                        'bullets' => [
                            'Capture the issue summary and category.',
                            'Store transcript and recording context.',
                            'Link the request to the caller history.',
                        ],
                    ],
                    [
                        'title' => 'Why this matters for customer experience',
                        'body' => 'Customers feel the difference when the callback starts with context instead of confusion. That makes the conversation shorter and more confident.',
                        'bullets' => [
                            'Faster follow-up conversations.',
                            'Fewer missed details from the first call.',
                            'Cleaner escalation to the right teammate.',
                        ],
                    ],
                    [
                        'title' => 'How teams use it in practice',
                        'body' => 'Support teams use tickIt when phone calls are part of the workflow but they still need the ticket, transcript, and follow-up path to stay aligned.',
                        'bullets' => [
                            'Customer support lines with repeat callers.',
                            'Service teams with billing or account questions.',
                            'Businesses that want to attach follow-up booking to the original case.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Customer support teams that rely on phone conversations.',
                    'Operations that want clearer issue categories and follow-up context.',
                    'Businesses that need transcripts tied to each support case.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Can tickIt work for customer support phone lines?',
                        'answer' => 'Yes. tickIt can answer support calls, capture issue details, create tickets, and keep the transcript and caller context attached to the support case.',
                    ],
                    [
                        'question' => 'Does it support repeat callers?',
                        'answer' => 'Yes. Contacts and caller history can help support teams understand repeat callers more quickly.',
                    ],
                    [
                        'question' => 'Can it handle multilingual support intake?',
                        'answer' => 'Yes. Multilingual assistant support helps teams serve callers in more than one language while keeping the call history better labeled.',
                    ],
                    [
                        'question' => 'Can follow-up booking stay linked to the support case?',
                        'answer' => 'Yes. Suggested or confirmed follow-up can stay attached to the related support case.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'call-transcripts', 'multilingual-voice-assistants'],
                'related_industries' => ['it-support', 'reception-and-front-desk'],
            ],
            'service-businesses' => [
                'slug' => 'service-businesses',
                'group' => 'industries',
                'label' => 'Industry',
                'nav_label' => 'Service businesses',
                'card_title' => 'For service businesses',
                'card_summary' => 'Capture inbound service calls, book the next step, and keep the caller, issue, and follow-up attached for field teams or office staff.',
                'meta_title' => 'AI phone answering for service businesses | tickIt',
                'meta_description' => 'Use AI phone answering for service businesses to capture inbound calls, create tickets, save transcripts, and help book the next step.',
                'hero_title' => 'AI phone answering for service businesses that live on inbound calls.',
                'hero_description' => 'tickIt helps service businesses capture customer calls, log the issue clearly, and hand office staff or field teams a cleaner request with transcript and callback context attached.',
                'direct_answer' => 'tickIt is useful for service businesses because it answers inbound customer calls, captures the reason for the call, and turns that intake into a ticket your team can act on fast.',
                'highlights' => ['Inbound service calls', 'Callback capture', 'Ticket workflow', 'Existing number import'],
                'benefits' => [
                    [
                        'title' => 'Turn the first call into a usable job record',
                        'body' => 'Instead of jotting down the basics and calling back later, the team starts with a structured request that includes the caller, issue, and next step.',
                    ],
                    [
                        'title' => 'Support office teams and field teams together',
                        'body' => 'The office can review the ticket and transcript before dispatching or following up, which makes field communication smoother.',
                    ],
                    [
                        'title' => 'Use the business number your customers already know',
                        'body' => 'tickIt supports clearer flows for provisioning, forwarding, or importing existing numbers, including US and international lines when the carrier path supports it.',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'What this looks like for a service business',
                        'body' => 'When customers call about an issue, they expect quick understanding and a clear next step. tickIt helps your team start there instead of piecing the request together later.',
                        'bullets' => [
                            'Answer the call with a branded assistant.',
                            'Capture the issue and callback details.',
                            'Save the request in a ticket your team can review and act on.',
                        ],
                    ],
                    [
                        'title' => 'Why this matters operationally',
                        'body' => 'Every missed detail can lead to a wasted callback, a poor visit, or a slower response. Better intake means fewer preventable delays.',
                        'bullets' => [
                            'Less manual note-taking for office staff.',
                            'Cleaner handoff into dispatch or follow-up.',
                            'Better visibility into repeat callers and past requests.',
                        ],
                    ],
                    [
                        'title' => 'Where teams usually adopt it first',
                        'body' => 'Service businesses often start with overflow coverage, after-hours intake, or one call line that creates the most admin burden today.',
                        'bullets' => [
                            'Overflow call coverage during busy periods.',
                            'After-hours message capture with better context.',
                            'Dedicated inbound line for service requests or customer callbacks.',
                        ],
                    ],
                ],
                'fit_points' => [
                    'Service businesses that still depend on phone calls for first contact.',
                    'Teams that want easier number forwarding or import flows.',
                    'Operations that need better intake before dispatch or callback.',
                ],
                'faq_items' => [
                    [
                        'question' => 'Can service businesses use tickIt with an existing phone number?',
                        'answer' => 'Yes. tickIt supports provisioning a new number, forwarding an existing number, or importing a current line depending on the carrier setup.',
                    ],
                    [
                        'question' => 'Can I import a German or US business number?',
                        'answer' => 'Yes. Teams can plan or configure imports for US +1 and international numbers such as German +49 lines when the Vapi BYO path and carrier setup are in place.',
                    ],
                    [
                        'question' => 'Is tickIt only for large teams?',
                        'answer' => 'No. Small service businesses often benefit quickly because every missed detail creates real administrative drag for a small office team.',
                    ],
                    [
                        'question' => 'Can it help with callback booking too?',
                        'answer' => 'Yes. tickIt can help save or confirm callback-related follow-up after the call is logged into a ticket.',
                    ],
                ],
                'related_features' => ['ai-phone-answering', 'meeting-booking', 'multilingual-voice-assistants'],
                'related_industries' => ['property-management', 'reception-and-front-desk'],
            ],
        ];
    }
}
