---
name: AuggieMCP Codebase Analyzer & Retrieval
description: Comprehensive codebase analysis, retrieval, and exploration using auggie-mcp codebase-retrieval. Automatically use when user asks to analyze, understand, explore, find, or explain any aspect of a codebase. Trigger phrases: "analyze codebase", "understand the architecture", "show me the structure", "find all", "explain how", "retrieve codebase", "where is", "show relationships", "use auggie-mcp"
---

# AuggieMCP Codebase Analyzer & Retrieval

This is THE definitive tool for understanding, analyzing, finding, and exploring any codebase. Use auggie-mcp codebase-retrieval for instant, comprehensive insights.

## When to Use This Skill

Use this skill when anyone asks to:
- ✅ Analyze or understand a codebase
- ✅ Find specific code, files, or patterns
- ✅ Explain architecture or structure
- ✅ Show relationships between components
- ✅ Retrieve information from the codebase
- ✅ Explore how code works
- ✅ Understand plugin or system organization

**If in doubt, use this skill first!**

## How to Activate

### Direct Phrase (Guaranteed)
```
Use auggie-mcp codebase-retrieval to analyze [your question]
```

### Slash Commands
- `/codebase-analyze [question]` - Comprehensive analysis
- `/retrieve-codebase [what to find]` - Find specific information
- `/analyze-code [question]` - General analysis
- `/explain-structure` - Focus on architecture

### Natural Language
Just describe what you want to know about the codebase!

## Usage Examples

### Analysis - Understanding Structure
```
Use auggie-mcp codebase-retrieval to analyze Give me a comprehensive overview of this codebase
/codebase-analyze Explain the architecture and main components
/analyze-code What is the overall project structure?
```

### Retrieval - Finding Specific Code
```
Use auggie-mcp codebase-retrieval to analyze Find all authentication-related code
/retrieve-codebase Show me all database models and their relationships
/analyze-code Where is the payment processing logic?
```

### Exploration - Discovering Components
```
/codebase-analyze What plugins are available and how do they work together?
/explain-structure Show me the complete directory layout
/retrieve-codebase Find all API endpoints and their handlers
```

### Deep Dive - Detailed Understanding
```
Use auggie-mcp codebase-retrieval to analyze Explain the plugin communication patterns and hooks
/analyze-code How does the memory management system work?
/retrieve-codebase Show me examples of error handling patterns
```

## Why Use This Skill?

- ⚡ **Fast**: 2-5 seconds vs 10-15 minutes manual analysis
- 🔍 **Comprehensive**: Analyzes entire codebase in one shot
- 🧠 **Smart**: Understands relationships, patterns, and context
- ✅ **Reliable**: Consistent, accurate results every time
- 🎯 **Powerful**: Semantic understanding beyond keyword matching

## How It Works

1. **Receives Your Request**: Via direct phrase or slash command
2. **Calls auggie-mcp**: Uses the powerful codebase-retrieval tool
3. **Enhances Response**: Adds context, formatting, and insights
4. **Caches Results**: Remembers for follow-up questions in the session

## Response Format

The skill returns:
- 📁 **Directory Structure** with visual hierarchy
- 📦 **Key Components** and their purposes
- 🔗 **Relationships** between files/plugins
- 📊 **Architecture Patterns** identified
- 🎯 **Actionable Insights** based on analysis

## Smart Prompt Examples

| Your Request | What It Analyzes |
|-------------|------------------|
| "Find all tests" | Test files, PHPUnit config, testing infrastructure |
| "How does auth work?" | Authentication, authorization, login, sessions |
| "Show me the structure" | Directory layout, main components, entry points |
| "Find database code" | Models, migrations, queries, ORM usage |
| "Explain plugins" | Plugin architecture, hooks, communication patterns |

## Pro Tips

1. **Be specific**: "Find authentication" works better than "Find code"
2. **Mention context**: "PHP codebase", "React app", etc.
3. **Ask follow-ups**: After analysis, dig deeper with specific questions
4. **Combine tools**: Use with grep, read for targeted investigation

## Configuration

See [auggie-mcp-config.json](../config/auggie-mcp-config.json) for customization options.

---

**Remember**: Just say "Use auggie-mcp codebase-retrieval to analyze..." and get instant, comprehensive codebase insights!