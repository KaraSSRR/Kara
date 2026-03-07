import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://www.example-salary-calculator.com"),
  title: {
    default: "Salary Calculator – Gross to Net & Net to Gross",
    template: "%s | Salary Calculator"
  },
  description:
    "Modern salary calculator for gross to net and net to gross salary, with localized explanations for countries, cities, and professions.",
  applicationName: "Salary Calculator",
  robots: {
    index: true,
    follow: true
  }
};

export default function RootLayout({
  children
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className="min-h-screen bg-background text-foreground">
        {children}
      </body>
    </html>
  );
}

