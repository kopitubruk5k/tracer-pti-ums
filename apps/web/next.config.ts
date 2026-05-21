import path from "path";

const nextConfig = {
  output: "export",
  trailingSlash: true,
  basePath: process.env.NEXT_PUBLIC_BASE_PATH || "",
  turbopack: {
    root: path.join(__dirname, '../../'),
  },
};

export default nextConfig;