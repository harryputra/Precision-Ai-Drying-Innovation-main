# Padi PRECISION — Agent Instructions

## Post-completion rule

After completing any implementation, bug fix, or feature task in this project, the agent must end its final reply with a ready-to-use `git commit` message (title + body in conventional-commit style) so the user can copy it into the commit dialog or press the sync button in their interface.

Format:

```text
type(scope): short description in English

- Bulleted summary of what changed.
- Mention new files and modified files briefly.
- Keep it concise and professional.
```

If the task did not touch files (e.g., only explanation), omit the commit message block.
