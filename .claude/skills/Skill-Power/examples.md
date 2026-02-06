# Skill-Power Examples

## Before and After Examples

### Example 1: Database Question
**User:** "How do I connect to a MySQL database?"

❌ **Wrong Response:**
"You can connect to MySQL using the mysqli extension..."

✅ **Correct Response:**
"I'm using the MySQL skill to help you connect to your database."

### Example 2: Code Fix Request
**User:** "This PHP code has an error, can you fix it?"

❌ **Wrong Response:**
"I can see the error on line 5. You're missing a semicolon..."

✅ **Correct Response:**
"I'm using the CodebaseErrorDetector skill to analyze and fix this PHP code."

### Example 3: Plugin Creation
**User:** "I want to create a new plugin for formatting code"

❌ **Wrong Response:**
"Let me help you create a code formatting plugin..."

✅ **Correct Response:**
"I'm using the Plugin_Maker skill to help you create your code formatting plugin."

### Example 4: Project Documentation
**User:** "Can you help me document my project?"

❌ **Wrong Response:**
"I can help you create documentation for your project..."

✅ **Correct Response:**
"I'm using the documentation skill to help you create project documentation."

## Edge Cases

### Uncertain Skill Match
**User:** "I need to work with some data"

✅ **Correct Response:**
"I'm using the database skill to handle your data management needs."

### Multiple Potential Skills
**User:** "I need to test my PHP code for errors"

✅ **Correct Response:**
"I'm using the PHP skill and the CodebaseErrorDetector skill to test and analyze your PHP code."

### Vague Request
**User:** "Help me with my project"

✅ **Correct Response:**
"Skill check complete: Multiple skills may apply. I'm using the ProjectMemoryManager skill to understand your project context."

## Critical Rules in Action

### Rule: 1% Chance = 100% Mandatory
**User:** "Maybe I should check if there are any issues with my code"

✅ **Correct Response:**
"I'm using the CodebaseErrorDetector skill to check your code for issues."

### Rule: No Rationalization
**User:** "Can you just quickly tell me what this error means?"

❌ **Wrong Internal Monologue:** "This is just a simple question, I don't need the error skill..."

✅ **Correct Response:**
"I'm using the error handling skill to explain this error and provide solutions."

### Rule: Announce Skill Usage
**User:** "I want to brainstorm features for my app"

✅ **Correct Response:**
"I'm using the brainstorming skill to generate feature ideas for your app."