describe("TrackingSpamPrevention – IP allowlist UI", function () {
    this.timeout(0);
    this.fixture = "Piwik\\Plugins\\TrackingSpamPrevention\\tests\\Fixtures\\ActivatePluginFixture";

    const pageUrl = "?module=CoreAdminHome&action=generalSettings";

    async function findPanel() {
        const candidates = [
            '[data-setting-id="iprange_allowlist"]',
            'section:has([name*="iprange_allowlist"])',
            'div.form-field:has(label:matches("(?i)allowlist|cidr|ip range"))',
        ];
        for (const sel of candidates) {
            const el = await page.$(sel).catch(()=>null);
            if (el) return sel;
        }
        throw new Error("IP allowlist control not found; tweak selectors.");
    }

    async function addRow(panelSel, value) {

        const addBtn = await page.$(`${panelSel} .addRow, ${panelSel} .icon-plus, ${panelSel} button`).catch(()=>null);
        if (addBtn) await addBtn.click();

        const inputs = await page.$$(`${panelSel} input[name*="[cidr]"], ${panelSel} input[name*="cidr"]`);
        if (!inputs.length) throw new Error("CIDR input not found");
        const last = inputs[inputs.length - 1];
        await last.click({ clickCount: 3 });
        await last.type(value);
    }

    it("renders empty state", async function () {
        await page.goto(pageUrl);
        const sel = await findPanel();
        await page.waitForSelector(sel, { timeout: 10000 });
        expect(await page.screenshotSelector(sel)).to.matchImage("tsp-ip-empty");
    });

    it("saves valid CIDRs and persists after reload", async function () {
        await page.goto(pageUrl);
        const sel = await findPanel();

        await addRow(sel, "127.0.0.1");
        await addRow(sel, "10.0.0.0/8");
        await addRow(sel, "2001:db8::/32");

        expect(await page.screenshotSelector(sel)).to.matchImage("tsp-ip-filled-before-save");

        const saveSel = 'button[type="submit"], button.saveButton, .adminSettings input[type="submit"]';
        await page.click(saveSel);
        await page.waitForNetworkIdle({ idleTime: 800, timeout: 10000 });

        await page.goto(pageUrl);
        await page.waitForSelector(sel, { timeout: 10000 });
        expect(await page.screenshotSelector(sel)).to.matchImage("tsp-ip-filled-after-save");
    });

    it("shows validation for invalid CIDR", async function () {
        await page.goto(pageUrl);
        const sel = await findPanel();

        await addRow(sel, "not_an_ip");
        const saveSel = 'button[type="submit"], button.saveButton, .adminSettings input[type="submit"]';
        await page.click(saveSel);
        await page.waitForTimeout(800);

        expect(await page.screenshotSelector(sel)).to.matchImage("tsp-ip-invalid");
    });
});
