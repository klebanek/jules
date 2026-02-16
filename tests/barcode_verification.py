from playwright.sync_api import sync_playwright
import os

def test_barcode_generation():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Absolute path to index.html
        path = os.path.abspath("index.html")
        page.goto(f"file://{path}")

        # Wait for the app to initialize
        page.wait_for_function("window.app !== undefined")

        results = page.evaluate("""
            () => {
                const app = window.app;
                const testResults = [];

                // Test 1: Format
                const barcode = app.generatePseudoBarcode();
                const isFormatCorrect = barcode.startsWith('NO') && barcode.length === 13;
                testResults.push({
                    name: "Format test",
                    passed: isFormatCorrect,
                    actual: barcode
                });

                // Test 2: isPseudoBarcode compatibility
                const isCompatible = app.isPseudoBarcode(barcode);
                testResults.push({
                    name: "isPseudoBarcode compatibility test",
                    passed: isCompatible === true
                });

                // Test 3: Uniqueness (high frequency)
                const count = 10;
                const barcodes = new Set();
                for(let i=0; i<count; i++) {
                    barcodes.add(app.generatePseudoBarcode());
                }
                testResults.push({
                    name: "Uniqueness test (10 iterations)",
                    passed: barcodes.size === count,
                    actual: barcodes.size,
                    expected: count
                });

                return testResults;
            }
        """)

        print("Test Results:")
        all_passed = True
        for result in results:
            status = "PASSED" if result['passed'] else "FAILED"
            print(f"- {result['name']}: {status}")
            if not result['passed']:
                all_passed = False
                if 'actual' in result:
                    print(f"  Actual: {result['actual']}")
                if 'expected' in result:
                    print(f"  Expected: {result['expected']}")

        browser.close()
        return all_passed

if __name__ == "__main__":
    success = test_barcode_generation()
    if not success:
        exit(1)
