import Footer from "./components/layout/Footer";
import Navbar from "./components/layout/Navbar";
import About from "./components/sections/About";
import Hero from "./components/sections/Hero";
import Location from "./components/sections/Location";
import Menu from "./components/sections/Menu";
import Reservation from "./components/sections/Reservation";

export default function App() {
  return (
    <div className="min-h-screen bg-[#FAF8F5]">
      <Navbar />
      <main>
        <Hero />
        <About />
        <Menu />
        <Reservation />
        <Location />
      </main>
      <Footer />
    </div>
  );
}
