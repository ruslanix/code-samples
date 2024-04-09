import * as dotenv from "dotenv";
import dotenvExpand from "dotenv-expand";

const envFilePath = `./.env.test`;
dotenvExpand(dotenv.config({
    path: envFilePath
}));
