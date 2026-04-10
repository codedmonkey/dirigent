import sys
import re


def filter_lines(text):
    """Remove irrelevant lines from the dump"""
    excluded_prefixes = ('SET ', 'SELECT ', 'GRANT ', 'REVOKE ')
    lines = [
        line for line in text.splitlines(keepends=True)
        if line.strip()
        and not line.startswith(excluded_prefixes)
        and not (line.startswith('\\') and not line.startswith('\\.'))
    ]
    return ''.join(lines)


def sort_copy_columns(text):
    """Sort columns in COPY blocks and reorder the data rows accordingly"""
    result = []
    lines = text.split('\n')
    i = 0
    while i < len(lines):
        copy_match = re.match(r'(COPY \S+ \()([^)]+)(\) FROM stdin;)', lines[i])
        if copy_match:
            cols = [c.strip() for c in copy_match.group(2).split(',')]
            sorted_indices = sorted(range(len(cols)), key=lambda j: cols[j])
            sorted_cols = [cols[j] for j in sorted_indices]
            result.append(copy_match.group(1) + ', '.join(sorted_cols) + copy_match.group(3))
            i += 1
            while i < len(lines) and lines[i] != '\\.':
                values = lines[i].split('\t')
                result.append('\t'.join(values[j] for j in sorted_indices))
                i += 1
            if i < len(lines):
                result.append(lines[i])  # \.
        else:
            result.append(lines[i])
        i += 1
    return '\n'.join(result)


def sort_create_table_columns(match):
    """Sort column lines in a CREATE TABLE block alphabetically"""
    lines = [
        line.strip().rstrip(',')
        for line in match.group(1).split('\n')
        if line.strip()
    ]
    return '(\n    ' + ',\n    '.join(sorted(lines)) + '\n)'


text = filter_lines(sys.stdin.read())
text = re.sub(r'\(((?:\n    [^\n]+)+)\n\)', sort_create_table_columns, text)
text = sort_copy_columns(text)
sys.stdout.write(text)
