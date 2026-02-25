from playwright.sync_api import sync_playwright
import os

def test_ean13_validation():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Absolute path to index.html
        path = os.path.abspath("index.html")
        page.goto(f"file://{path}")

        # Wait for the app to initialize
        page.wait_for_function("window.app !== undefined")

        test_cases = [
            {"barcode": "4006381333931", "expected": True, "name": "Valid EAN-13 (4006381333931)"},
            {"barcode": "9780131103627", "expected": True, "name": "Valid EAN-13 (9780131103627)"},
            {"barcode": "0000000000000", "expected": True, "name": "Valid EAN-13 (All zeros)"},
            {"barcode": "4006381333932", "expected": False, "name": "Invalid checksum"},
            {"barcode": "400638133393", "expected": False, "name": "Too short"},
            {"barcode": "40063813339311", "expected": False, "name": "Too long"},
            {"barcode": "400638133393A", "expected": False, "name": "Non-digit character at end"},
            {"barcode": "A006381333931", "expected": False, "name": "Non-digit character at start"},
            {"barcode": "40063B1333931", "expected": False, "name": "Non-digit character in middle"},
            {"barcode": "             ", "expected": False, "name": "All spaces"},
            {"barcode": "", "expected": False, "name": "Empty string"},
        ]

        results = page.evaluate(f"""
            (testCases) => {{
                const app = window.app;
                return testCases.map(tc => {{
                    const actual = app.isValidEAN13(tc.barcode);
                    return {{
                        name: tc.name,
                        passed: actual === tc.expected,
                        actual: actual,
                        expected: tc.expected,
                        barcode: tc.barcode
                    }};
                }});
            }}
        """, test_cases)

        print("EAN-13 Validation Test Results:")
        all_passed = True
        for result in results:
            status = "PASSED" if result['passed'] else "FAILED"
            print(f"- {result['name']}: {status}")
            if not result['passed']:
                all_passed = False
                print(f"  Barcode: {result['barcode']}")
                print(f"  Actual: {result['actual']}")
                print(f"  Expected: {result['expected']}")

        browser.close()
        return all_passed

if __name__ == "__main__":
    success = test_ean13_validation()
    if not success:
        exit(1)
