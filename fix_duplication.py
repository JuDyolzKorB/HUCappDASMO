
import os

file_path = r'c:\Users\Jules\Documents\GitHub\HUCappDASMO\pages\settings.php'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# indices are 0-based. Line 425 is index 424.
start_index = 424
end_index = 468 # inclusive of the block, so slice up to this

# Verification
line_425 = lines[start_index].strip()
line_426 = lines[start_index+1].strip()

print(f"Line 425: {line_425}")
print(f"Line 426: {line_426[:50]}...")

expected_425 = "<!-- Security Tab -->"
expected_426_start = "<div x-show=\"activeTab === 'security'\""

if line_425 == expected_425 and line_426.startswith(expected_426_start):
    print("Verification passed. Removing duplicated block.")
    # Keep lines before start_index and lines after the block
    # We want to remove lines 425 to 468 (indices 424 to 467)
    # so we keep 0..423 and 468..end
    
    # Let's verify end index
    # Line 468 is index 467.
    # So we want to keep from index 468 (which is line 469).
    
    new_lines = lines[:start_index] + lines[end_index:]
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    print("File updated successfully.")
else:
    print("Verification FAILED. File not modified.")
    print(f"Expected 425: '{expected_425}', Got: '{line_425}'")
    print(f"Expected 426 start: '{expected_426_start}', Got: '{line_426[:50]}'")
