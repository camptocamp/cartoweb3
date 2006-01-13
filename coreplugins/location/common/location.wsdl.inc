      <!-- location -->
 
      <complexType name="LocationRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="locationType"  type="types:LocationType"/>
          <element name="bboxLocationRequest" type="types:BboxLocationRequest" minOccurs="0"/>
          <element name="panLocationRequest" type="types:PanLocationRequest" minOccurs="0"/>
          <element name="zoomPointLocationRequest" type="types:ZoomPointLocationRequest" minOccurs="0"/>
          <element name="recenterLocationRequest" type="types:RecenterLocationRequest" minOccurs="0"/>
          <element name="locationConstraint" type="types:LocationConstraint" minOccurs="0"/>
        </all>
      </complexType>     

      <complexType name="LocationResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="bbox" type="types:Bbox"/>
          <element name="scale" type="xsd:double"/>
        </all>
      </complexType>
            
      <complexType name="ArrayOfLocationScale">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LocationScale[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="LocationScale">
        <all>
          <element name="label" type="xsd:string"/>
          <element name="value" type="xsd:double"/>
        </all>
      </complexType>
            
      <complexType name="ArrayOfLocationShortcut">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LocationShortcut[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="LocationShortcut">
        <all>
          <element name="label" type="xsd:string"/>
          <element name="bbox" type="types:Bbox"/>
        </all>
      </complexType>
            
      <complexType name="LocationInit">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="scales" type="types:ArrayOfLocationScale"/>
          <element name="minScale" type="xsd:double"/>
          <element name="maxScale" type="xsd:double"/>
          <element name="shortcuts" type="types:ArrayOfLocationShortcut"/>
        </all>
      </complexType>

      <complexType name="LocationConstraint">
        <all>
          <element name="maxBbox" type="types:Bbox"/>
        </all>
      </complexType>
                  
      <simpleType name="LocationType">
        <restriction base="xsd:string">
          <enumeration value="bboxLocationRequest"/>
          <enumeration value="panLocationRequest"/>
          <enumeration value="zoomPointLocationRequest"/>
          <enumeration value="recenterLocationRequest"/>
        </restriction>
      </simpleType>

      <complexType name="BboxLocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
          <element name="showRefMarks" type="xsd:boolean"/>
        </all>
      </complexType>

      <simpleType name="PanDirectionType">
        <restriction base="xsd:string">
          <enumeration value="VERTICAL_PAN_NORTH"/>
          <enumeration value="VERTICAL_PAN_NONE"/>
          <enumeration value="VERTICAL_PAN_SOUTH"/>
          <enumeration value="HORIZONTAL_PAN_WEST"/>
          <enumeration value="HORIZONTAL_PAN_NONE"/>
          <enumeration value="HORIZONTAL_PAN_EAST"/>
        </restriction>
      </simpleType>

      <complexType name="PanDirection">
        <all>
          <element name="verticalPan" type="types:PanDirectionType"/>
          <element name="horizontalPan" type="types:PanDirectionType"/>
        </all>
      </complexType>

      <complexType name="PanLocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
          <element name="showRefMarks" type="xsd:boolean"/>
          <element name="panDirection" type="types:PanDirection"/>
        </all>
      </complexType>

      <simpleType name="ZoomType">
        <restriction base="xsd:string">
          <enumeration value="ZOOM_DIRECTION_IN"/>
          <enumeration value="ZOOM_DIRECTION_NONE"/>
          <enumeration value="ZOOM_DIRECTION_OUT"/>
          <enumeration value="ZOOM_FACTOR"/>
          <enumeration value="ZOOM_SCALE"/>
        </restriction>
      </simpleType>

      <complexType name="ZoomPointLocationRequest">
        <all>
          <element name="bbox" type="types:Bbox"/>
          <element name="showRefMarks" type="xsd:boolean"/>
          <element name="point" type="types:Point"/>
          <element name="zoomType" type="types:ZoomType"/>
          <element name="zoomFactor" type="xsd:float" minOccurs="0"/>
          <element name="scale" type="xsd:integer" minOccurs="0"/>
        </all>
      </complexType>

      <complexType name="ArrayOfIdSelection">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:IdSelection[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="IdSelection">
        <all>
          <element name="layerId" type="xsd:string"/>
          <element name="idAttribute" type="xsd:string"/>
          <!-- should be a struct (string|integer) -->
          <element name="idType" type="xsd:string"/>
          <!-- FIXME: integer should be supported also -->
          <element name="selectedIds" type="types:ArrayOfString"/>
        </all>
      </complexType>

      <complexType name="RecenterLocationRequest">
        <all>
          <element name="idSelections" type="types:ArrayOfIdSelection"/>
          <element name="fallbackBbox" type="types:Bbox"/>
        </all>
      </complexType>
