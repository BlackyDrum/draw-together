export type CanvasPoint = {
    x: number;
    y: number;
};

export type RoomStroke = {
    id: string;
    session_id: string;
    display_name: string;
    tool: 'pen' | 'brush' | 'eraser';
    color: string | null;
    size: number;
    points: CanvasPoint[];
    position: number;
};

export type RoomParticipant = {
    session_id: string;
    display_name: string;
    color: string;
    is_viewer: boolean;
};

export type RoomData = {
    name: string;
    code: string;
    invite_url: string;
};

export type RoomPageProps = {
    room: RoomData;
    strokes: RoomStroke[];
    participants: RoomParticipant[];
    viewer: RoomParticipant | null;
    errors: Record<string, string>;
    [key: string]: unknown;
};
