{{ $schema }}

Given the database schema above, generate a syntactically correct SQL query to answer the following question.

Rules:
@if($strict_mode)
    1. Generate ONLY SELECT queries (no INSERT, UPDATE, DELETE, DROP, etc.)
@endif
2. Use proper table and column names from the schema
3. Return ONLY the SQL query without explanations
4. Do not include markdown code blocks
5. Do not include semicolon at the end
6. Make the query efficient and accurate

Question: "{{ $question }}"

Generate the SQL query now: