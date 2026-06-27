---
description: AI-driven development cycle for Panglong ERP - analyze, prompt, test, fix iteratively
---

# AI Development Cycle Workflow

## Overview
This workflow implements an autonomous AI-driven development cycle for continuously improving and perfecting the Panglong ERP application.

## Cycle Steps

### Phase 1: Deep Analysis
1. **Analyze current application state**
   - Review all frontend PHP files (45 pages)
   - Review ajax.php endpoints (48 endpoints)
   - Review database schema (78 tables)
   - Review documentation files
   - Identify code quality issues
   - Identify missing features
   - Identify UX improvements
   - Identify performance bottlenecks

2. **Document findings**
   - Create analysis report
   - Prioritize issues by severity
   - Categorize by type (bug, feature, improvement, optimization)

### Phase 2: Internet Research
1. **Research best practices**
   - ERP system development best practices
   - PHP 8.2+ coding standards
   - SQLite optimization techniques
   - jQuery/Bootstrap modern patterns
   - Security best practices for web applications
   - UI/UX trends for ERP systems

2. **Research similar systems**
   - Open source ERP solutions
   - Industry standards for inventory management
   - Accounting system patterns
   - Multi-tenant SaaS architecture

### Phase 3: Create Development Prompt
1. **Generate comprehensive prompt**
   - Based on analysis findings
   - Based on research insights
   - Include specific actionable tasks
   - Include code examples where applicable
   - Include testing requirements
   - Include success criteria

2. **Save prompt to file**
   - File: `.devin/prompts/development-iteration-{n}.md`
   - Include timestamp
   - Include priority level
   - Include estimated complexity

### Phase 4: Execute Development
1. **Implement changes**
   - Follow prompt instructions
   - Make code changes
   - Update documentation
   - Maintain backward compatibility
   - Follow existing code patterns

2. **Code review**
   - Self-review changes
   - Check for regressions
   - Verify coding standards
   - Ensure security best practices

### Phase 5: Testing
1. **Run automated tests**
   ```bash
   npx playwright test
   ```

2. **Manual testing**
   - Test affected features
   - Test related features
   - Test edge cases
   - Test with different user roles

3. **Document test results**
   - Record pass/fail status
   - Document any issues found
   - Take screenshots if needed

### Phase 6: Fix Issues
1. **Address test failures**
   - Fix bugs found during testing
   - Address UX issues
   - Fix performance problems
   - Update documentation

2. **Re-test**
   - Run tests again
   - Verify fixes
   - Ensure no regressions

### Phase 7: Update Prompt
1. **Refine development prompt**
   - Incorporate lessons learned
   - Adjust based on test results
   - Add new requirements discovered
   - Remove completed tasks

2. **Increment iteration number**
   - Save refined prompt as next iteration
   - Archive previous prompt

### Phase 8: Repeat
1. **Continue cycle**
   - Go back to Phase 1
   - Focus on remaining issues
   - Continuously improve application
   - Until application is "perfect"

## Success Criteria

### Code Quality
- No PHP errors or warnings
- No JavaScript console errors
- Follow PSR-12 coding standards
- Consistent code style across files
- Proper error handling
- Input validation on all endpoints
- SQL injection prevention
- XSS prevention

### Functionality
- All features work as expected
- No broken workflows
- Consistent behavior across roles
- Data integrity maintained
- Transactions handled correctly

### Performance
- Page load time < 2 seconds
- AJAX response time < 500ms
- Database queries optimized
- No memory leaks
- Efficient resource usage

### Security
- Session management working
- Permission checks on all operations
- SQL injection protected
- XSS protected
- CSRF protected
- Secure password handling
- Audit logging for sensitive operations

### User Experience
- Intuitive navigation
- Clear error messages
- Responsive design works
- Dark mode works
- Eye-care mode works
- Mobile-friendly
- Keyboard shortcuts where appropriate

### Testing
- All Playwright tests passing
- Manual testing complete
- Edge cases covered
- Different user roles tested

## Exit Conditions

The cycle continues until ALL of the following are met:

1. **Zero critical bugs** - No crashes, data loss, or security vulnerabilities
2. **Zero high-priority issues** - All important features work correctly
3. **All tests passing** - 100% test pass rate
4. **Code quality score** - Meets or exceeds industry standards
5. **Performance benchmarks** - All performance targets met
6. **Documentation complete** - All features documented
7. **User acceptance** - Application meets all business requirements

## Notes

- Each iteration should focus on a specific area or set of related issues
- Maintain backward compatibility whenever possible
- Always test before and after changes
- Document all changes made
- Keep the database schema stable
- Use existing patterns and conventions
- Prioritize stability over new features
- The goal is perfection, not just completion
